<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\EmpresaModel;
use App\Models\EmpresaUsuarioModel;
use CodeIgniter\API\ResponseTrait;
use Firebase\JWT\JWT;

/**
 * Controller de Autenticação Corporativa e Gestão de Sessão Multiempresa
 */
class AuthController extends BaseController
{
    use ResponseTrait;

    protected UserModel $userModel;
    protected EmpresaModel $empresaModel;
    protected EmpresaUsuarioModel $empresaUsuarioModel;

    public function __construct()
    {
        $this->userModel           = model(UserModel::class);
        $this->empresaModel        = model(EmpresaModel::class);
        $this->empresaUsuarioModel = model(EmpresaUsuarioModel::class);
    }

    /**
     * Cadastro individual de usuário
     * POST /api/register
     */
    public function register()
    {
        $rules = [
            'name'     => 'required|min_length[3]|max_length[100]',
            'phone'    => 'required|is_unique[users.phone]',
            'password' => 'required|min_length[6]',
        ];

        $messages = [
            'name' => [
                'required'   => 'Por favor, informe seu nome completo.',
                'min_length' => 'O nome deve ter no mínimo 3 caracteres.',
            ],
            'phone' => [
                'required'  => 'O número de telefone é obrigatório.',
                'is_unique' => 'Este número de telefone já está cadastrado na plataforma.',
            ],
            'password' => [
                'required'   => 'A senha de acesso é obrigatória.',
                'min_length' => 'A senha deve conter pelo menos 6 caracteres.',
            ],
        ];

        if (!$this->validate($rules, $messages)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $userId = $this->userModel->createUser([
            'name'     => $this->request->getVar('name'),
            'phone'    => $this->request->getVar('phone'),
            'password' => $this->request->getVar('password'),
        ]);

        return $this->respondCreated([
            'status'  => 201,
            'message' => 'Conta criada com sucesso! Para acessar um ambiente corporativo, cadastre sua empresa ou aceite um convite.',
            'data'    => [
                'user_id' => $userId,
            ],
        ]);
    }

    /**
     * Autenticação corporativa com contexto de tenant e geração de token JWT
     * POST /api/login
     */
    public function login()
    {
        $rules = [
            'phone'    => 'required',
            'password' => 'required',
        ];

        $messages = [
            'phone'    => ['required' => 'Informe seu telefone de acesso.'],
            'password' => ['required' => 'Informe sua senha.'],
        ];

        if (!$this->validate($rules, $messages)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $phone = $this->request->getVar('phone');
        $password = $this->request->getVar('password');
        $requestedEmpresaId = (int) ($this->request->getVar('empresa_id') ?? 0);

        $user = $this->userModel->findByPhone($phone);

        if (!$user || !password_verify($password, $user['password'])) {
            return $this->failUnauthorized('Credenciais inválidas. Verifique seu telefone e senha.');
        }

        // Obtém as empresas ativas às quais este usuário pertence
        $empresas = $this->empresaUsuarioModel->getEmpresasDoUsuario((int) $user['id']);

        if (empty($empresas)) {
            return $this->failForbidden('Sua conta não possui vínculo ativo com nenhuma organização corporativa. Solicite um convite ao administrador da sua empresa.');
        }

        // Determina a empresa ativa da sessão
        $empresaAtiva = null;
        if ($requestedEmpresaId > 0) {
            foreach ($empresas as $emp) {
                if ((int) $emp['id'] === $requestedEmpresaId) {
                    $empresaAtiva = $emp;
                    break;
                }
            }
            if (!$empresaAtiva) {
                return $this->failForbidden('Você não possui autorização para acessar esta empresa.');
            }
        } else {
            // Padrão: primeira empresa vinculada
            $empresaAtiva = $empresas[0];
        }

        $key = getenv('JWT_SECRET_KEY');
        $iat = time();
        $exp = $iat + (int) (getenv('JWT_TIME_TO_LIVE') ?: 604800);

        $payload = [
            'iss'  => 'tchat-api',
            'sub'  => (int) $user['id'],
            'iat'  => $iat,
            'exp'  => $exp,
            'data' => [
                'id'         => (int) $user['id'],
                'name'       => $user['name'],
                'phone'      => $user['phone'],
                'empresa_id' => (int) $empresaAtiva['id'],
                'cargo'      => $empresaAtiva['cargo'],
            ],
        ];

        $token = JWT::encode($payload, $key, 'HS256');

        return $this->respond([
            'status'  => 200,
            'message' => 'Autenticação realizada com sucesso!',
            'token'   => $token,
            'user'    => [
                'id'         => (int) $user['id'],
                'name'       => $user['name'],
                'phone'      => $user['phone'],
                'avatar_url' => $user['avatar_url'],
                'cargo'      => $empresaAtiva['cargo'],
            ],
            'empresa' => [
                'id'             => (int) $empresaAtiva['id'],
                'nome'           => $empresaAtiva['nome'],
                'nome_fantasia'  => $empresaAtiva['nome_fantasia'],
                'logo_url'       => $empresaAtiva['logo_url'],
                'cor_primaria'   => $empresaAtiva['cor_primaria'],
                'cor_secundaria' => $empresaAtiva['cor_secundaria'],
            ],
            'empresas_disponiveis' => $empresas,
        ]);
    }

    /**
     * Alternar a empresa ativa da sessão (gerando novo JWT com o novo contexto)
     * POST /api/empresa/trocar (Requer AuthFilter)
     */
    public function trocarEmpresa()
    {
        $loggedUser = $this->request->user ?? null;

        if (!$loggedUser) {
            return $this->failUnauthorized('Usuário não autenticado.');
        }

        $novaEmpresaId = (int) ($this->request->getVar('empresa_id') ?? 0);

        if (!$novaEmpresaId) {
            return $this->failValidationErrors(['empresa_id' => 'Informe o ID da empresa para a qual deseja alternar.']);
        }

        $vinculo = $this->empresaUsuarioModel->getVinculo((int) $loggedUser->id, $novaEmpresaId);

        if (!$vinculo || $vinculo['status'] !== 'ativo' || !$vinculo['empresa_ativa']) {
            return $this->failForbidden('Você não possui acesso ativo a esta organização.');
        }

        $empresa = $this->empresaModel->findById($novaEmpresaId);

        $key = getenv('JWT_SECRET_KEY');
        $iat = time();
        $exp = $iat + (int) (getenv('JWT_TIME_TO_LIVE') ?: 604800);

        $payload = [
            'iss'  => 'tchat-api',
            'sub'  => (int) $loggedUser->id,
            'iat'  => $iat,
            'exp'  => $exp,
            'data' => [
                'id'         => (int) $loggedUser->id,
                'name'       => $loggedUser->name,
                'phone'      => $loggedUser->phone,
                'empresa_id' => $novaEmpresaId,
                'cargo'      => $vinculo['cargo'],
            ],
        ];

        $token = JWT::encode($payload, $key, 'HS256');

        return $this->respond([
            'status'  => 200,
            'message' => "Ambiente corporativo alternado para {$empresa['nome']} com sucesso!",
            'token'   => $token,
            'empresa' => [
                'id'             => (int) $empresa['id'],
                'nome'           => $empresa['nome'],
                'nome_fantasia'  => $empresa['nome_fantasia'],
                'logo_url'       => $empresa['logo_url'],
                'cor_primaria'   => $empresa['cor_primaria'],
                'cor_secundaria' => $empresa['cor_secundaria'],
            ],
        ]);
    }

    /**
     * Retorna o perfil completo do usuário autenticado no ambiente corporativo atual
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

        $empresa = $this->empresaModel->findById((int) $loggedUser->empresa_id);
        $empresas = $this->empresaUsuarioModel->getEmpresasDoUsuario((int) $loggedUser->id);

        return $this->respond([
            'status' => 200,
            'data'   => [
                'user' => [
                    'id'         => (int) $user['id'],
                    'name'       => $user['name'],
                    'phone'      => $user['phone'],
                    'avatar_url' => $user['avatar_url'],
                    'cargo'      => $loggedUser->cargo,
                ],
                'empresa_atual' => $empresa ? [
                    'id'             => (int) $empresa['id'],
                    'nome'           => $empresa['nome'],
                    'nome_fantasia'  => $empresa['nome_fantasia'],
                    'cnpj'           => $empresa['cnpj'],
                    'logo_url'       => $empresa['logo_url'],
                    'cor_primaria'   => $empresa['cor_primaria'],
                    'cor_secundaria' => $empresa['cor_secundaria'],
                ] : null,
                'minhas_empresas' => $empresas,
            ],
        ]);
    }

    /**
     * Atualização da foto de perfil (avatar) do usuário
     * POST /api/me/avatar (Requer AuthFilter)
     */
    public function updateAvatar()
    {
        $loggedUser = $this->request->user ?? null;

        if (!$loggedUser) {
            return $this->failUnauthorized('Usuário não autenticado.');
        }

        $avatarFile = $this->request->getFile('avatar');
        $avatarUrl = $this->request->getVar('avatar_url');

        if ($avatarFile && $avatarFile->isValid() && !$avatarFile->hasMoved()) {
            $storage = new \App\Libraries\R2Storage();
            $uploadData = $storage->upload($avatarFile, 'avatars');
            $avatarUrl = $uploadData['url'];
        }

        if (empty($avatarUrl)) {
            return $this->failValidationErrors(['avatar' => 'Envie uma imagem de avatar ou informe a URL da foto.']);
        }

        $this->userModel->updateAvatar((int) $loggedUser->id, $avatarUrl);

        return $this->respond([
            'status'  => 200,
            'message' => 'Foto de perfil atualizada com sucesso!',
            'data'    => [
                'avatar_url' => $avatarUrl,
            ],
        ]);
    }
}