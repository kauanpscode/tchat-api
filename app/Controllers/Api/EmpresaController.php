<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\EmpresaModel;
use App\Models\EmpresaUsuarioModel;
use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;
use Firebase\JWT\JWT;

/**
 * Controller de Gestão de Empresas e Identidade Visual (Branding Corporativo)
 */
class EmpresaController extends BaseController
{
    use ResponseTrait;

    protected EmpresaModel $empresaModel;
    protected EmpresaUsuarioModel $empresaUsuarioModel;
    protected UserModel $userModel;

    public function __construct()
    {
        $this->empresaModel        = model(EmpresaModel::class);
        $this->empresaUsuarioModel = model(EmpresaUsuarioModel::class);
        $this->userModel           = model(UserModel::class);
    }

    /**
     * Cadastro de nova Empresa e criação do Administrador inicial
     * POST /api/empresas/registrar (Público)
     */
    public function registrar()
    {
        $rules = [
            'empresa_nome' => 'required|min_length[3]|max_length[150]',
            'cnpj'         => 'permit_empty|min_length[14]|max_length[20]',
            'admin_name'   => 'required|min_length[3]|max_length[100]',
            'admin_phone'  => 'required',
            'admin_pass'   => 'required|min_length[6]',
        ];

        $messages = [
            'empresa_nome' => [
                'required'   => 'O nome da empresa ou razão social é obrigatório.',
                'min_length' => 'O nome da empresa deve ter no mínimo 3 caracteres.',
            ],
            'admin_name' => [
                'required' => 'O nome do administrador é obrigatório.',
            ],
            'admin_phone' => [
                'required' => 'O telefone do administrador é obrigatório para login.',
            ],
            'admin_pass' => [
                'required'   => 'A senha do administrador é obrigatória.',
                'min_length' => 'A senha deve conter pelo menos 6 caracteres.',
            ],
        ];

        if (!$this->validate($rules, $messages)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $cnpj = $this->request->getVar('cnpj');
        if (!empty($cnpj) && $this->empresaModel->findByCnpj($cnpj)) {
            return $this->failResourceExists('Já existe uma organização cadastrada com este CNPJ na plataforma.');
        }

        $adminPhone = $this->request->getVar('admin_phone');
        $existingUser = $this->userModel->findByPhone($adminPhone);

        // Se o usuário já existe, verifica a senha
        $adminUserId = null;
        if ($existingUser) {
            if (!password_verify($this->request->getVar('admin_pass'), $existingUser['password'])) {
                return $this->failUnauthorized('O telefone informado já pertence a um usuário, mas a senha fornecida está incorreta.');
            }
            $adminUserId = (int) $existingUser['id'];
        } else {
            // Cria o usuário administrador
            $adminUserId = $this->userModel->createUser([
                'name'     => $this->request->getVar('admin_name'),
                'phone'    => $adminPhone,
                'password' => $this->request->getVar('admin_pass'),
            ]);
        }

        // Cria a Empresa
        $empresaId = $this->empresaModel->createEmpresa([
            'nome'           => $this->request->getVar('empresa_nome'),
            'nome_fantasia'  => $this->request->getVar('nome_fantasia') ?: $this->request->getVar('empresa_nome'),
            'cnpj'           => $cnpj,
            'logo_url'       => $this->request->getVar('logo_url'),
            'cor_primaria'   => $this->request->getVar('cor_primaria') ?: '#075E54',
            'cor_secundaria' => $this->request->getVar('cor_secundaria') ?: '#128C7E',
        ]);

        // Vincula o usuário como Administrador da Empresa
        $this->empresaUsuarioModel->adicionarMembro($empresaId, $adminUserId, 'admin');

        // Gera token JWT de acesso imediato
        $key = getenv('JWT_SECRET_KEY');
        $iat = time();
        $exp = $iat + (int) (getenv('JWT_TIME_TO_LIVE') ?: 604800);

        $payload = [
            'iss'  => 'tchat-api',
            'sub'  => $adminUserId,
            'iat'  => $iat,
            'exp'  => $exp,
            'data' => [
                'id'         => $adminUserId,
                'name'       => $this->request->getVar('admin_name'),
                'phone'      => $adminPhone,
                'empresa_id' => $empresaId,
                'cargo'      => 'admin',
            ],
        ];

        $token = JWT::encode($payload, $key, 'HS256');
        $empresa = $this->empresaModel->findById($empresaId);

        return $this->respondCreated([
            'status'  => 201,
            'message' => "Empresa '{$empresa['nome']}' cadastrada com sucesso! Seu ambiente corporativo está pronto.",
            'token'   => $token,
            'empresa' => $empresa,
        ]);
    }

    /**
     * Retorna os dados e identidade visual da empresa do usuário autenticado
     * GET /api/empresa (Requer AuthFilter)
     */
    public function getDados()
    {
        $loggedUser = $this->request->user ?? null;

        if (!$loggedUser) {
            return $this->failUnauthorized('Usuário não autenticado.');
        }

        $empresa = $this->empresaModel->findById((int) $loggedUser->empresa_id);

        if (!$empresa) {
            return $this->failNotFound('Empresa não encontrada.');
        }

        return $this->respond([
            'status' => 200,
            'data'   => $empresa,
        ]);
    }

    /**
     * Atualiza as informações e identidade visual da empresa (cores, logo, nome)
     * PUT /api/empresa (Requer AuthFilter + AdminFilter)
     */
    public function atualizar()
    {
        $loggedUser = $this->request->user ?? null;

        if (!$loggedUser) {
            return $this->failUnauthorized('Usuário não autenticado.');
        }

        $data = [];
        if ($this->request->getVar('nome')) {
            $data['nome'] = $this->request->getVar('nome');
        }
        if ($this->request->getVar('nome_fantasia')) {
            $data['nome_fantasia'] = $this->request->getVar('nome_fantasia');
        }
        if ($this->request->getVar('logo_url')) {
            $data['logo_url'] = $this->request->getVar('logo_url');
        }
        if ($this->request->getVar('cor_primaria')) {
            $data['cor_primaria'] = $this->request->getVar('cor_primaria');
        }
        if ($this->request->getVar('cor_secundaria')) {
            $data['cor_secundaria'] = $this->request->getVar('cor_secundaria');
        }
        if ($this->request->getVar('configuracoes')) {
            $data['configuracoes'] = $this->request->getVar('configuracoes');
        }

        if (empty($data)) {
            return $this->failValidationErrors(['body' => 'Nenhum dado informado para atualização.']);
        }

        $this->empresaModel->updateBranding((int) $loggedUser->empresa_id, $data);
        $empresaAtualizada = $this->empresaModel->findById((int) $loggedUser->empresa_id);

        return $this->respond([
            'status'  => 200,
            'message' => 'Identidade visual e dados corporativos atualizados com sucesso!',
            'data'    => $empresaAtualizada,
        ]);
    }
}
