<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\EmpresaConviteModel;
use App\Models\EmpresaUsuarioModel;
use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;
use Firebase\JWT\JWT;

/**
 * Controller de Gestão de Convites Corporativos
 */
class ConviteController extends BaseController
{
    use ResponseTrait;

    protected EmpresaConviteModel $conviteModel;
    protected EmpresaUsuarioModel $empresaUsuarioModel;
    protected UserModel $userModel;

    public function __construct()
    {
        $this->conviteModel        = model(EmpresaConviteModel::class);
        $this->empresaUsuarioModel = model(EmpresaUsuarioModel::class);
        $this->userModel           = model(UserModel::class);
    }

    /**
     * Envia um convite para novo colaborador
     * POST /api/empresa/convites (Requer AuthFilter + AdminFilter)
     */
    public function criar()
    {
        $loggedUser = $this->request->user ?? null;

        if (!$loggedUser) {
            return $this->failUnauthorized('Usuário não autenticado.');
        }

        $email = $this->request->getVar('email');
        $telefone = $this->request->getVar('telefone');
        $cargo = $this->request->getVar('cargo') ?: 'colaborador';

        if (empty($email) && empty($telefone)) {
            return $this->failValidationErrors(['contato' => 'Informe o e-mail ou o telefone do colaborador para o convite.']);
        }

        if (!in_array($cargo, ['admin', 'colaborador', 'gerente'])) {
            return $this->failValidationErrors(['cargo' => 'Cargo inválido. Escolha entre: admin, gerente, colaborador.']);
        }

        $convite = $this->conviteModel->criarConvite(
            (int) $loggedUser->empresa_id,
            (int) $loggedUser->id,
            $email,
            $telefone,
            $cargo
        );

        return $this->respondCreated([
            'status'  => 201,
            'message' => 'Convite gerado com sucesso! Envie o token ou link de acesso para o colaborador.',
            'data'    => $convite,
        ]);
    }

    /**
     * Lista todos os convites da empresa
     * GET /api/empresa/convites (Requer AuthFilter + AdminFilter)
     */
    public function listar()
    {
        $loggedUser = $this->request->user ?? null;

        if (!$loggedUser) {
            return $this->failUnauthorized('Usuário não autenticado.');
        }

        $convites = $this->conviteModel->listarConvites((int) $loggedUser->empresa_id);

        return $this->respond([
            'status' => 200,
            'data'   => $convites,
        ]);
    }

    /**
     * Cancela um convite pendente
     * DELETE /api/empresa/convites/{id} (Requer AuthFilter + AdminFilter)
     */
    public function cancelar($id = null)
    {
        $loggedUser = $this->request->user ?? null;

        if (!$loggedUser) {
            return $this->failUnauthorized('Usuário não autenticado.');
        }

        $sucesso = $this->conviteModel->cancelarConvite((int) $id, (int) $loggedUser->empresa_id);

        if (!$sucesso) {
            return $this->failNotFound('Convite pendente não encontrado ou já processado.');
        }

        return $this->respond([
            'status'  => 200,
            'message' => 'Convite cancelado com sucesso.',
        ]);
    }

    /**
     * Visualiza os dados do convite antes de aceitar
     * GET /api/convites/{token} (Público)
     */
    public function visualizar($token = null)
    {
        if (empty($token)) {
            return $this->failValidationErrors(['token' => 'Token do convite não fornecido.']);
        }

        $convite = $this->conviteModel->buscarPorToken($token);

        if (!$convite) {
            return $this->failNotFound('Convite não encontrado ou token inválido.');
        }

        if ($convite['status'] !== 'pendente') {
            return $this->failForbidden("Este convite já foi {$convite['status']}.");
        }

        return $this->respond([
            'status' => 200,
            'data'   => [
                'empresa_nome'          => $convite['empresa_nome'],
                'empresa_logo'          => $convite['empresa_logo'],
                'empresa_cor_primaria'  => $convite['empresa_cor_primaria'],
                'cargo_oferecido'       => $convite['cargo'],
                'convidado_por'         => $convite['convidado_por_nome'],
                'expira_em'             => $convite['expira_em'],
            ],
        ]);
    }

    /**
     * Aceita o convite e ingressa na organização corporativa
     * POST /api/convites/{token}/aceitar (Público / Autenticado)
     */
    public function aceitar($token = null)
    {
        if (empty($token)) {
            return $this->failValidationErrors(['token' => 'Token do convite não informado.']);
        }

        $convite = $this->conviteModel->buscarPorToken($token);

        if (!$convite) {
            return $this->failNotFound('Convite não encontrado.');
        }

        if ($convite['status'] !== 'pendente') {
            return $this->failForbidden("Este convite não está mais disponível ({$convite['status']}).");
        }

        $userId = null;
        $userName = null;
        $userPhone = null;

        // Se o usuário já está autenticado via token na requisição
        $authHeader = $this->request->getServer('HTTP_AUTHORIZATION');
        if ($authHeader) {
            $arr = explode(' ', $authHeader);
            $bearerToken = $arr[1] ?? null;
            if ($bearerToken) {
                try {
                    $key = getenv('JWT_SECRET_KEY');
                    $decoded = JWT::decode($bearerToken, new \Firebase\JWT\Key($key, 'HS256'));
                    $userId = (int) $decoded->data->id;
                    $user = $this->userModel->findById($userId);
                    if ($user) {
                        $userName = $user['name'];
                        $userPhone = $user['phone'];
                    }
                } catch (\Exception $e) {
                    // Ignora e prossegue para dados enviados no corpo
                }
            }
        }

        // Se não autenticado, exige dados de cadastro/login
        if (!$userId) {
            $phone = $this->request->getVar('phone');
            $password = $this->request->getVar('password');

            if (empty($phone) || empty($password)) {
                return $this->failValidationErrors([
                    'credenciais' => 'Informe seu telefone e senha para aceitar o convite e vincular sua conta.',
                ]);
            }

            $user = $this->userModel->findByPhone($phone);

            if ($user) {
                if (!password_verify($password, $user['password'])) {
                    return $this->failUnauthorized('Senha incorreta para o telefone informado.');
                }
                $userId = (int) $user['id'];
                $userName = $user['name'];
                $userPhone = $user['phone'];
            } else {
                // Cria novo usuário
                $name = $this->request->getVar('name');
                if (empty($name)) {
                    return $this->failValidationErrors(['name' => 'Informe seu nome completo para criar a conta.']);
                }

                $userId = $this->userModel->createUser([
                    'name'     => $name,
                    'phone'    => $phone,
                    'password' => $password,
                ]);
                $userName = $name;
                $userPhone = $phone;
            }
        }

        // Vincula o usuário à empresa com o cargo do convite
        $this->empresaUsuarioModel->adicionarMembro(
            (int) $convite['empresa_id'],
            $userId,
            $convite['cargo']
        );

        // Marca convite como aceito
        $this->conviteModel->marcarComoAceito((int) $convite['id']);

        // Gera token JWT de acesso ao ambiente corporativo
        $key = getenv('JWT_SECRET_KEY');
        $iat = time();
        $exp = $iat + (int) (getenv('JWT_TIME_TO_LIVE') ?: 604800);

        $payload = [
            'iss'  => 'tchat-api',
            'sub'  => $userId,
            'iat'  => $iat,
            'exp'  => $exp,
            'data' => [
                'id'         => $userId,
                'name'       => $userName,
                'phone'      => $userPhone,
                'empresa_id' => (int) $convite['empresa_id'],
                'cargo'      => $convite['cargo'],
            ],
        ];

        $jwtToken = JWT::encode($payload, $key, 'HS256');

        return $this->respond([
            'status'  => 200,
            'message' => "Convite aceito! Bem-vindo(a) à equipe da empresa '{$convite['empresa_nome']}'.",
            'token'   => $jwtToken,
            'empresa' => [
                'id'             => (int) $convite['empresa_id'],
                'nome'           => $convite['empresa_nome'],
                'logo_url'       => $convite['empresa_logo'],
                'cor_primaria'   => $convite['empresa_cor_primaria'],
            ],
        ]);
    }
}
