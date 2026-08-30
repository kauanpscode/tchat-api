<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ConversaModel;
use CodeIgniter\API\ResponseTrait;

class ConversaController extends BaseController
{
    use ResponseTrait;

    public function __construct()
    {
        $this->model = model(ConversaModel::class);
    }
    public function getConversas()
    {
        $loggedUser = $this->request->user ?? null;

        if (!$loggedUser) {
            return $this->failUnauthorized('Usuário não autenticado.');
        }

        $user = $this->model->findById((int) $loggedUser->id);

        if (!$user) {
            return $this->failNotFound('Usuário não encontrado.');
        }

        return $this->respond([
            'status' => 200,
            'data' => $user,
        ]);
    }
}