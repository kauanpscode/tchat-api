<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ConversaModel;
use App\Models\MensagemModel;
use App\Models\PushModel;
use App\Libraries\R2Storage;
use CodeIgniter\API\ResponseTrait;

/**
 * Controller de Histórico e Envio de Mensagens Corporativas com Push Notifications
 */
class MensagemController extends BaseController
{
    use ResponseTrait;

    protected MensagemModel $mensagemModel;
    protected ConversaModel $conversaModel;
    protected PushModel $pushModel;
    protected R2Storage $storage;

    public function __construct()
    {
        $this->mensagemModel = model(MensagemModel::class);
        $this->conversaModel = model(ConversaModel::class);
        $this->pushModel     = model(PushModel::class);
        $this->storage       = new R2Storage();
    }

    /**
     * Retorna o histórico de mensagens de uma conversa pertencente à empresa do usuário
     * GET /api/conversas/{id}/mensagens (Requer AuthFilter)
     */
    public function index(int $conversaId)
    {
        $loggedUser = $this->request->user ?? null;

        if (!$loggedUser) {
            return $this->failUnauthorized('Usuário não autenticado.');
        }

        // Validação de isolamento corporativo: conversa deve pertencer à empresa ativa
        if (!$this->conversaModel->pertenceAEmpresa($conversaId, (int) $loggedUser->empresa_id)) {
            return $this->failForbidden('Acesso negado: Esta conversa não pertence à sua organização corporativa.');
        }

        // Validação de participação: usuário deve ser membro da conversa
        if (!$this->conversaModel->isParticipante($conversaId, (int) $loggedUser->id)) {
            return $this->failForbidden('Acesso negado: Você não participa desta conversa.');
        }

        // Paginação simples
        $pagina = (int) ($this->request->getVar('pagina') ?: 1);
        $limite = (int) ($this->request->getVar('limite') ?: 40);
        $offset = ($pagina - 1) * $limite;

        $mensagens = $this->mensagemModel->getHistorico($conversaId, (int) $loggedUser->id, $limite, $offset);

        // Marca as mensagens como lidas automaticamente ao visualizar o chat
        $this->conversaModel->marcarComoLida($conversaId, (int) $loggedUser->id);

        return $this->respond([
            'status'       => 200,
            'data'         => $mensagens,
            'pagina_atual' => $pagina,
            'limite'       => $limite,
        ]);
    }

    /**
     * Envia uma nova mensagem na conversa corporativa (com suporte a anexos e push)
     * POST /api/mensagens (Requer AuthFilter)
     */
    public function enviar()
    {
        $loggedUser = $this->request->user ?? null;

        if (!$loggedUser) {
            return $this->failUnauthorized('Usuário não autenticado.');
        }

        $rules = [
            'id_conversa' => 'required|is_natural_no_zero',
            'mensagem'    => 'permit_empty',
        ];

        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $conversaId = (int) $this->request->getVar('id_conversa');

        // Validação de isolamento corporativo
        if (!$this->conversaModel->pertenceAEmpresa($conversaId, (int) $loggedUser->empresa_id)) {
            return $this->failForbidden('Acesso negado: Esta conversa não pertence à sua organização corporativa.');
        }

        // Validação de participação
        if (!$this->conversaModel->isParticipante($conversaId, (int) $loggedUser->id)) {
            return $this->failForbidden('Acesso negado: Você não participa desta conversa.');
        }

        $mensagem = $this->request->getVar('mensagem') ?? '';
        $anexo = $this->request->getFile('anexo');

        if (empty($mensagem) && (!$anexo || !$anexo->isValid())) {
            return $this->failValidationErrors('Você deve enviar um texto ou um arquivo em anexo.');
        }

        $tipo = 'texto';
        if ($anexo && $anexo->isValid() && !$anexo->hasMoved()) {
            $mime = $anexo->getMimeType();
            if (strpos($mime, 'image/') === 0) {
                $tipo = 'imagem';
            } elseif (strpos($mime, 'audio/') === 0) {
                $tipo = 'audio';
            } else {
                $tipo = 'arquivo';
            }
        }

        $data = [
            'id_conversa'    => $conversaId,
            'id_usuario'     => (int) $loggedUser->id,
            'tipo'           => $tipo,
            'mensagem'       => $mensagem,
            'respondendo_id' => $this->request->getVar('respondendo_id') ?: null,
            'client_id'      => $this->request->getVar('client_id'),
        ];

        $mensagemId = $this->mensagemModel->enviarMensagem($data);

        // Upload do anexo (se houver)
        $uploadUrl = null;
        if ($anexo && $anexo->isValid() && !$anexo->hasMoved()) {
            $uploadData = $this->storage->upload($anexo, 'chat_anexos');
            $this->mensagemModel->salvarAnexo($mensagemId, $uploadData, $tipo);
            $uploadUrl = $uploadData['url'];
        }

        // Notificação Push aos demais participantes
        $outrosUsuarios = $this->mensagemModel->getOutrosParticipantesIds($conversaId, (int) $loggedUser->id);
        $tokens = $this->pushModel->getTokensByUserIds($outrosUsuarios);

        if (!empty($tokens)) {
            $msgTextoPush = ($tipo !== 'texto') ? "📷 Novo {$tipo} enviado" : $data['mensagem'];
            $this->notificarExpoPush($tokens, $loggedUser->name, $msgTextoPush, $conversaId);
        }

        return $this->respondCreated([
            'status'  => 201,
            'message' => 'Mensagem enviada com sucesso!',
            'data'    => [
                'id'        => $mensagemId,
                'anexo_url' => $uploadUrl,
            ],
        ]);
    }

    /**
     * Dispara notificação push via Expo Push API
     */
    private function notificarExpoPush(array $tokens, string $senderName, string $message, int $conversaId): void
    {
        $pushMessage = mb_strimwidth($message, 0, 50, '...');
        $messages = [];

        foreach ($tokens as $token) {
            $messages[] = [
                'to'    => $token,
                'sound' => 'default',
                'title' => "Nova mensagem de {$senderName}",
                'body'  => $pushMessage,
                'data'  => ['conversa_id' => $conversaId],
            ];
        }

        try {
            $client = \Config\Services::curlrequest();
            $client->post('https://exp.host/--/api/v2/push/send', [
                'headers' => [
                    'Accept'          => 'application/json',
                    'Accept-Encoding' => 'gzip, deflate',
                    'Content-Type'    => 'application/json',
                ],
                'json'        => $messages,
                'http_errors' => false,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Falha ao enviar Push Notification: ' . $e->getMessage());
        }
    }
}
