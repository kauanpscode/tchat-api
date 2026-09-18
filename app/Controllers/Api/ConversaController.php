<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ConversaModel;
use CodeIgniter\API\ResponseTrait;
use App\Libraries\R2Storage;

class ConversaController extends BaseController
{
    protected R2Storage $storage;

    use ResponseTrait;


    public function __construct()
    {
        $this->model = model(ConversaModel::class);
        $this->storage     = new R2Storage();
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

    public function updateAvatar()
    {
        $loggedUser = $this->request->user ?? null;

        if (!$loggedUser) {
            return $this->failUnauthorized('Usuário não autenticado.');
        }

        $rules = [
            'avatar' => 'uploaded[avatar]|is_image[avatar]|max_size[avatar,2048]|mime_in[avatar,image/jpg,image/jpeg,image/png,image/webp]',
        ];

        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $file = $this->request->getFile('avatar');

        $upload = $this->storage->upload($file, 'avatars');

        $this->model->updateAvatar((int) $loggedUser->id, $upload['url']);

        return $this->respond([
            'status' => 200,
            'message' => 'Foto de perfil atualizada com sucesso!',
            'data' => [
                'avatar_url' => $upload['url'],
            ],
        ]);
    }
}
