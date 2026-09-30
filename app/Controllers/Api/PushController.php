<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\PushModel;
use CodeIgniter\API\ResponseTrait;

class PushController extends BaseController
{
    use ResponseTrait;

    protected PushModel $pushModel;

    public function __construct()
    {
        $this->pushModel = model(PushModel::class);
    }

    /**
     * POST /api/push/register
     * Salva o Token do dispositivo móvel do usuário logado
     */
    public function register()
    {
        $loggedUser = $this->request->user ?? null;

        if (!$loggedUser) {
            return $this->failUnauthorized('Usuário não autenticado.');
        }

        $rules = [
            'token' => 'required',
            'plataforma' => 'permit_empty|in_list[android,ios]'
        ];

        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $token = $this->request->getVar('token');
        $plataforma = $this->request->getVar('plataforma') ?? 'android';

        $this->pushModel->salvarToken((int) $loggedUser->id, $token, $plataforma);

        return $this->respond([
            'status' => 200,
            'message' => 'Token de push registrado com sucesso.'
        ]);
    }

    /**
     * POST /api/push/webhook
     * Hook para receber os recibos do serviço de Push (Ex: Expo / Firebase)
     * Isso garante que mantemos apenas tokens válidos.
     */
    public function webhook()
    {
        // Geralmente webhooks enviam payloads em JSON raw
        $json = $this->request->getJSON();

        if (!$json) {
            return $this->fail('Payload inválido');
        }

        // Exemplo processando recibos do Expo Notifications
        // Onde ele informa se um token falhou com "DeviceNotRegistered"
        if (is_array($json)) {
            foreach ($json as $receipt) {
                if (isset($receipt->status) && $receipt->status === 'error') {
                    if (isset($receipt->details->error) && $receipt->details->error === 'DeviceNotRegistered') {
                        // O usuário desinstalou o app ou revogou a permissão.
                        // Removemos o token do banco.
                        // Obs: O Expo precisa enviar o token no payload customizado que configuramos ao enviar a requisição
                        if (isset($receipt->device_token)) {
                            $this->pushModel->removerToken($receipt->device_token);
                        }
                    }
                }
            }
        }

        return $this->respond([
            'status' => 200,
            'message' => 'Webhook processado'
        ]);
    }
}
