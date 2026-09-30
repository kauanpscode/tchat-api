<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\MensagemModel;
use App\Models\PushModel;
use App\Libraries\R2Storage;
use CodeIgniter\API\ResponseTrait;

class MensagemController extends BaseController
{
    use ResponseTrait;

    protected MensagemModel $mensagemModel;
    protected PushModel $pushModel;
    protected R2Storage $storage;

    public function __construct()
    {
        $this->mensagemModel = model(MensagemModel::class);
        $this->pushModel = model(PushModel::class);
        $this->storage = new R2Storage();
    }

    /**
     * GET /api/conversas/{id}/mensagens
     */
    public function index(int $conversaId)
    {
        $loggedUser = $this->request->user ?? null;

        if (!$loggedUser) {
            return $this->failUnauthorized('Usuário não autenticado.');
        }

        // Paginação simples
        $pagina = (int) $this->request->getVar('pagina') ?: 1;
        $limite = 40;
        $offset = ($pagina - 1) * $limite;

        $mensagens = $this->mensagemModel->getHistorico($conversaId, $limite, $offset);

        return $this->respond([
            'status' => 200,
            'data' => $mensagens,
            'pagina_atual' => $pagina
        ]);
    }

    /**
     * POST /api/mensagens
     * Suporta JSON ou multipart/form-data para envio de anexo (arquivo, imagem, áudio)
     */
    public function enviar()
    {
        $loggedUser = $this->request->user ?? null;

        if (!$loggedUser) {
            return $this->failUnauthorized('Usuário não autenticado.');
        }

        $rules = [
            'id_conversa' => 'required|is_natural_no_zero',
            // Mensagem agora pode ser vazia se for apenas o envio de um arquivo
            'mensagem' => 'permit_empty'
        ];

        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        
        $mensagem = $this->request->getVar('mensagem') ?? '';
        $anexo = $this->request->getFile('anexo');
        
        if (empty($mensagem) && (!$anexo || !$anexo->isValid())) {
             return $this->failValidationErrors('Você deve enviar um texto ou um anexo.');
        }

        $tipo = 'texto';
        if ($anexo && $anexo->isValid() && !$anexo->hasMoved()) {
             $mime = $anexo->getMimeType();
             if (strpos($mime, 'image/') === 0) $tipo = 'imagem';
             elseif (strpos($mime, 'audio/') === 0) $tipo = 'audio';
             else $tipo = 'arquivo';
        }

        $data = [
            'id_conversa' => (int) $this->request->getVar('id_conversa'),
            'id_usuario' => (int) $loggedUser->id,
            'tipo' => $tipo,
            'mensagem' => $mensagem,
            'respondendo_id' => $this->request->getVar('respondendo_id') ?: null,
            'client_id' => $this->request->getVar('client_id')
        ];

        $mensagemId = $this->mensagemModel->enviarMensagem($data);
        
        // Trata o upload do arquivo
        $uploadUrl = null;
        if ($anexo && $anexo->isValid() && !$anexo->hasMoved()) {
             $uploadData = $this->storage->upload($anexo, 'chat_anexos');
             $this->mensagemModel->salvarAnexo($mensagemId, $uploadData, $tipo);
             $uploadUrl = $uploadData['url'];
        }

        // --- INTEGRAÇÃO PUSH NOTIFICATIONS ---
        $outrosUsuarios = $this->mensagemModel->getOutrosParticipantesIds($data['id_conversa'], $data['id_usuario']);
        
        $tokens = $this->pushModel->getTokensByUserIds($outrosUsuarios);
        if (!empty($tokens)) {
            $msgTextoPush = $tipo !== 'texto' ? "📷 Novo $tipo enviado" : $data['mensagem'];
            $this->notificarExpoPush($tokens, $loggedUser->name, $msgTextoPush, $data['id_conversa']);
        }

        return $this->respondCreated([
            'status' => 201,
            'message' => 'Mensagem enviada',
            'data' => [
                'id' => $mensagemId,
                'anexo_url' => $uploadUrl
            ]
        ]);
    }

    /**
     * Dispara notificação Push via Expo Push API.
     */
    private function notificarExpoPush(array $tokens, string $senderName, string $message, int $conversaId)
    {
        // Limita o tamanho da mensagem para a notificação
        $pushMessage = mb_strimwidth($message, 0, 50, '...');
        $messages = [];

        foreach ($tokens as $token) {
            $messages[] = [
                "to" => $token,
                "sound" => "default",
                "title" => "Nova mensagem de $senderName",
                "body" => $pushMessage,
                "data" => ["conversa_id" => $conversaId]
            ];
        }

        try {
            $client = \Config\Services::curlrequest();
            
            // Dispara para a API do Expo de forma síncrona (num app real, usaríamos filas/jobs)
            $response = $client->post('https://exp.host/--/api/v2/push/send', [
                'headers' => [
                    'Accept'          => 'application/json',
                    'Accept-Encoding' => 'gzip, deflate',
                    'Content-Type'    => 'application/json',
                ],
                'json' => $messages,
                'http_errors' => false // Para não lançar exception em 4xx/5xx e podermos logar
            ]);

            log_message('info', 'Expo Push Status: ' . $response->getStatusCode() . ' | Body: ' . $response->getBody());
        } catch (\Exception $e) {
            log_message('error', 'Falha ao enviar Push Notification: ' . $e->getMessage());
        }
    }
}
