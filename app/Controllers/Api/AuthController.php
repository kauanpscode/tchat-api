<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;
use Firebase\JWT\JWT;

class AuthController extends BaseController
{
    use ResponseTrait;
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel   = model(UserModel::class);
    }

    /**
     * Cadastro de novo usuário
     * POST /api/register
     */
    public function register()
    {
        $rules = [
            'name' => 'required|min_length[3]|max_length[100]',
            'phone' => 'required|is_unique[users.phone]',
            'password' => 'required|min_length[6]',
        ];

        $messages = [
            'name' => [
                'required' => 'O nome é obrigatório.',
                'min_length' => 'O nome deve ter pelo menos 3 caracteres.',
            ],
            'phone' => [
                'required' => 'O telefone é obrigatório.',
                'is_unique' => 'Este número de telefone já está cadastrado.',
            ],
            'password' => [
                'required' => 'A senha é obrigatória.',
                'min_length' => 'A senha deve ter pelo menos 6 caracteres.',
            ],
        ];

        if (!$this->validate($rules, $messages)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $userId = $this->userModel->createUser([
            'name' => $this->request->getVar('name'),
            'phone' => $this->request->getVar('phone'),
            'password' => $this->request->getVar('password'),
        ]);

        return $this->respondCreated([
            'status' => 201,
            'message' => 'Usuário cadastrado com sucesso!',
            'data' => [
                'user_id' => $userId,
            ],
        ]);
    }

    /**
     * Autenticação e geração de token JWT
     * POST /api/login
     */
    public function login()
    {
        $rules = [
            'phone' => 'required',
            'password' => 'required',
        ];

        $messages = [
            'phone' => ['required' => 'Informe o telefone para entrar.'],
            'password' => ['required' => 'Informe a senha.'],
        ];

        if (!$this->validate($rules, $messages)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $phone = $this->request->getVar('phone');
        $password = $this->request->getVar('password');

        $user = $this->userModel->findByPhone($phone);

        if (!$user || !password_verify($password, $user['password'])) {
            return $this->failUnauthorized('Telefone ou senha inválidos.');
        }

        $key = getenv('JWT_SECRET_KEY');
        $iat = time();
        $exp = $iat + (int) (getenv('JWT_TIME_TO_LIVE') ?: 604800);

        $payload = [
            'iss' => 'tchat-api',
            'sub' => $user['id'],
            'iat' => $iat,
            'exp' => $exp,
            'data' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'phone' => $user['phone'],
            ],
        ];

        $token = JWT::encode($payload, $key, 'HS256');

        return $this->respond([
            'status' => 200,
            'token' => $token,
            'user' => [
                'id' => (int) $user['id'],
                'name' => $user['name'],
                'phone' => $user['phone'],
                'avatar_url' => $user['avatar_url'],
            ],
        ]);
    }

    /**
     * Dados do perfil do usuário autenticado
     * GET /api/me (Requer AuthFilter)
     */
    public function me()
    {
        $loggedUser = $this->request->user ?? null;

        if (!$loggedUser) {
            return $this->failUnauthorized('Usuário não autenticado.');
        }

        $user = $this->userModel->findById((int) $loggedUser->id);

        if (!$user) {
            return $this->failNotFound('Usuário não encontrado.');
        }

        return $this->respond([
            'status' => 200,
            'data' => $user,
        ]);
    }
}