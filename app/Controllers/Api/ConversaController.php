<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ConversaModel;
use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;

class ConversaController extends BaseController
{
    use ResponseTrait;

    protected ConversaModel $conversaModel;
    protected UserModel $userModel;

    public function __construct()
    {
        $this->conversaModel = model(ConversaModel::class);
        $this->userModel = model(UserModel::class);
    }

    /**
     * GET /api/conversas
     * Retorna a lista de chats do usuário logado.
     */
    public function index()
    {
        $loggedUser = $this->request->user ?? null;

        if (!$loggedUser) {
            return $this->failUnauthorized('Usuário não autenticado.');
        }

        $conversas = $this->conversaModel->getUserConversas((int) $loggedUser->id);

        return $this->respond([
            'status' => 200,
            'data' => $conversas,
        ]);
    }

    /**
     * POST /api/conversas/privada
     * Inicia ou retorna um chat 1-a-1 com outro usuário.
     */
    public function criarPrivada()
    {
        $loggedUser = $this->request->user ?? null;

        if (!$loggedUser) {
            return $this->failUnauthorized('Usuário não autenticado.');
        }

        $targetUserId = $this->request->getVar('target_user_id');

        if (!$targetUserId) {
            return $this->failValidationErrors('O ID do usuário de destino é obrigatório.');
        }

        // Verifica se o usuário destino existe
        $targetUser = $this->userModel->findById((int) $targetUserId);
        if (!$targetUser) {
            return $this->failNotFound('Usuário de destino não encontrado.');
        }

        if ($loggedUser->id == $targetUserId) {
            return $this->failValidationErrors('Você não pode criar uma conversa consigo mesmo.');
        }

        $conversaId = $this->conversaModel->getOrCreatePrivada((int) $loggedUser->id, (int) $targetUserId);

        return $this->respond([
            'status' => 200,
            'message' => 'Conversa pronta.',
            'data' => [
                'conversa_id' => $conversaId
            ]
        ]);
    }
}
