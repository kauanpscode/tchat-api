<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ConversaModel;
use App\Models\EmpresaUsuarioModel;
use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;

/**
 * Controller de Gestão de Conversas Isoladas por Empresa (Multi-tenancy)
 */
class ConversaController extends BaseController
{
    use ResponseTrait;

    protected ConversaModel $conversaModel;
    protected EmpresaUsuarioModel $empresaUsuarioModel;
    protected UserModel $userModel;

    public function __construct()
    {
        $this->conversaModel       = model(ConversaModel::class);
        $this->empresaUsuarioModel = model(EmpresaUsuarioModel::class);
        $this->userModel           = model(UserModel::class);
    }

    /**
     * Lista todas as conversas do colaborador dentro do ambiente da empresa atual
     * GET /api/conversas (Requer AuthFilter)
     */
    public function index()
    {
        $loggedUser = $this->request->user ?? null;

        if (!$loggedUser) {
            return $this->failUnauthorized('Usuário não autenticado.');
        }

        $conversas = $this->conversaModel->getConversasDoUsuario(
            (int) $loggedUser->id,
            (int) $loggedUser->empresa_id
        );

        return $this->respond([
            'status' => 200,
            'data'   => $conversas,
        ]);
    }

    /**
     * Inicia ou recupera uma conversa privada (1-a-1) com outro colaborador da mesma empresa
     * POST /api/conversas/privada (Requer AuthFilter)
     */
    public function criarPrivada()
    {
        $loggedUser = $this->request->user ?? null;

        if (!$loggedUser) {
            return $this->failUnauthorized('Usuário não autenticado.');
        }

        $destinatarioId = $this->request->getVar('destinatario_id') ?? $this->request->getVar('target_user_id');

        if (!$destinatarioId) {
            return $this->failValidationErrors(['destinatario_id' => 'Informe o ID do colaborador destinatário.']);
        }

        $destinatarioId = (int) $destinatarioId;

        if ($destinatarioId === (int) $loggedUser->id) {
            return $this->failValidationErrors(['destinatario_id' => 'Você não pode iniciar uma conversa consigo mesmo.']);
        }

        // Validação estrita de Multi-tenancy: O destinatário DEVE ser colaborador ativo da mesma empresa
        $destinatarioMembro = $this->empresaUsuarioModel->isMembroAtivo(
            (int) $loggedUser->empresa_id,
            $destinatarioId
        );

        if (!$destinatarioMembro) {
            return $this->failForbidden('Acesso negado: O colaborador selecionado não pertence ao quadro ativo da sua empresa.');
        }

        $conversa = $this->conversaModel->getOrCreatePrivada(
            (int) $loggedUser->empresa_id,
            (int) $loggedUser->id,
            $destinatarioId
        );

        return $this->respond([
            'status'  => 200,
            'message' => $conversa['recem_criada'] ? 'Conversa iniciada com sucesso!' : 'Conversa existente recuperada.',
            'data'    => [
                'conversa_id'  => $conversa['id'],
                'empresa_id'   => $conversa['empresa_id'],
                'recem_criada' => $conversa['recem_criada'],
            ],
        ]);
    }

    /**
     * Marca todas as mensagens da conversa como lidas
     * POST /api/conversas/{id}/ler (Requer AuthFilter)
     */
    public function marcarComoLida($conversaId = null)
    {
        $loggedUser = $this->request->user ?? null;

        if (!$loggedUser) {
            return $this->failUnauthorized('Usuário não autenticado.');
        }

        $conversaId = (int) $conversaId;

        if (!$this->conversaModel->pertenceAEmpresa($conversaId, (int) $loggedUser->empresa_id)) {
            return $this->failForbidden('Acesso negado: Esta conversa não pertence à sua organização corporativa.');
        }

        if (!$this->conversaModel->isParticipante($conversaId, (int) $loggedUser->id)) {
            return $this->failForbidden('Acesso negado: Você não participa desta conversa.');
        }

        $this->conversaModel->marcarComoLida($conversaId, (int) $loggedUser->id);

        return $this->respond([
            'status'  => 200,
            'message' => 'Conversa marcada como lida.',
        ]);
    }

    /**
     * Cria um novo grupo de conversa corporativo
     * POST /api/conversas/grupo (Requer AuthFilter)
     */
    public function criarGrupo()
    {
        $loggedUser = $this->request->user ?? null;

        if (!$loggedUser) {
            return $this->failUnauthorized('Usuário não autenticado.');
        }

        $nome = $this->request->getVar('nome');
        if (empty($nome)) {
            return $this->failValidationErrors(['nome' => 'O nome do grupo é obrigatório.']);
        }

        $descricao = $this->request->getVar('descricao');
        $imagem = $this->request->getVar('imagem');
        $participantesIds = $this->request->getVar('participantes_ids') ?: [];

        if (is_string($participantesIds)) {
            $participantesIds = json_decode($participantesIds, true) ?: explode(',', $participantesIds);
        }

        // Valida se todos os participantes pertencem à mesma empresa
        foreach ($participantesIds as $pId) {
            $pId = (int) $pId;
            if ($pId !== (int) $loggedUser->id && !$this->empresaUsuarioModel->isMembroAtivo((int) $loggedUser->empresa_id, $pId)) {
                return $this->failForbidden("O colaborador com ID {$pId} não pertence à sua organização.");
            }
        }

        $conversaId = $this->conversaModel->criarGrupo(
            (int) $loggedUser->empresa_id,
            (int) $loggedUser->id,
            $nome,
            $descricao,
            $imagem,
            $participantesIds
        );

        return $this->respondCreated([
            'status'  => 201,
            'message' => "Grupo '{$nome}' criado com sucesso!",
            'data'    => [
                'conversa_id' => $conversaId,
                'nome'        => $nome,
            ],
        ]);
    }
}
