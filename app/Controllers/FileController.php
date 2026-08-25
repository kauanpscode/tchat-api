<?php

namespace App\Controllers;

use App\Libraries\R2Storage;

class FileController extends BaseController
{
    private R2Storage $storage;

    public function __construct()
    {
        $this->storage = new R2Storage();
    }

    public function upload()
    {
        $file = $this->request->getFile('file');

        if (!$file || !$file->isValid()) {
            return $this->response->setJSON([
                'error' => 'Arquivo inválido'
            ])->setStatusCode(400);
        }

        $result = $this->storage->upload($file, 'uploads');

        return $this->response->setJSON([
            'message' => 'Upload realizado com sucesso',
            'data' => $result
        ]);
    }

    public function delete()
    {
        $json = $this->request->getJSON(true);

        if (!isset($json['key'])) {
            return $this->response->setJSON([
                'error' => 'Key não informada'
            ])->setStatusCode(400);
        }

        $this->storage->delete($json['key']);

        return $this->response->setJSON([
            'message' => 'Arquivo deletado com sucesso'
        ]);
    }
}
