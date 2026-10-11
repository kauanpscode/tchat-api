<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\EmpresaUsuarioModel;
use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;

/**
 * Controller de Gestão de Colaboradores da Organização
 */
class ColaboradorController extends BaseController
{
    use ResponseTrait;

    protected EmpresaUsuarioModel $empresaUsuarioModel;
    protected UserModel $userModel;

    public function __construct()
    {
        $this->empresaUsuarioModel = model(EmpresaUsuarioModel::class);
        $this->userModel           = model(UserModel::class);
    }

    /**
     * Lista todos os colaboradores da empresa do usuário autenticado
     * GET /api/empresa/colaboradores (Requer AuthFilter)
     */
    public function listar()
    {
        $loggedUser = $this->request->user ?? null;

        if (!$loggedUser) {
            return $this->failUnauthorized('Usuário não autenticado.');
        }

        // Se for admin, pode passar ?todos=true para ver inativos também; colaborador comum só vê ativos
        $isAdmin = ($loggedUser->cargo === 'admin');
        $somenteAtivos = !$isAdmin || ($this->request->getVar('todos') !== 'true');

        $colaboradores = $this->empresaUsuarioModel->getColaboradores((int) $loggedUser->empresa_id, $somenteAtivos);

        return $this->respond([
            'status' => 200,
            'data'   => $colaboradores,
        ]);
    }

    /**
     * Altera o status de um colaborador (ativo, inativo, suspenso)
     * PATCH /api/empresa/colaboradores/{id}/status (Requer AuthFilter + AdminFilter)
     */
    public function alterarStatus($targetUserId = null)
    {
        $loggedUser = $this->request->user ?? null;

        if (!$loggedUser) {
            return $this->failUnauthorized('Usuário não autenticado.');
        }

        $targetUserId = (int) $targetUserId;
        $novoStatus = $this->request->getVar('status');

        if (!in_array($novoStatus, ['ativo', 'inativo', 'suspenso'])) {
            return $this->failValidationErrors(['status' => 'Status inválido. Escolha entre: ativo, inativo, suspenso.']);
        }

        // Não permite que o admin desative a si próprio
        if ($targetUserId === (int) $loggedUser->id && $novoStatus !== 'ativo') {
            return $this->failForbidden('Você não pode desativar seu próprio acesso de administrador.');
        }

        $vinculo = $this->empresaUsuarioModel->getVinculo($targetUserId, (int) $loggedUser->empresa_id);

        if (!$vinculo) {
            return $this->failNotFound('Colaborador não encontrado nesta empresa.');
        }

        $this->empresaUsuarioModel->alterarStatus((int) $loggedUser->empresa_id, $targetUserId, $novoStatus);

        return $this->respond([
            'status'  => 200,
            'message' => "Status do colaborador atualizado para '{$novoStatus}' com sucesso!",
        ]);
    }

    /**
     * Altera o cargo de um colaborador (admin, colaborador, gerente)
     * PATCH /api/empresa/colaboradores/{id}/cargo (Requer AuthFilter + AdminFilter)
     */
    public function alterarCargo($targetUserId = null)
    {
        $loggedUser = $this->request->user ?? null;

        if (!$loggedUser) {
            return $this->failUnauthorized('Usuário não autenticado.');
        }

        $targetUserId = (int) $targetUserId;
        $novoCargo = $this->request->getVar('cargo');

        if (!in_array($novoCargo, ['admin', 'colaborador', 'gerente'])) {
            return $this->failValidationErrors(['cargo' => 'Cargo inválido. Escolha entre: admin, gerente, colaborador.']);
        }

        $vinculo = $this->empresaUsuarioModel->getVinculo($targetUserId, (int) $loggedUser->empresa_id);

        if (!$vinculo) {
            return $this->failNotFound('Colaborador não encontrado nesta empresa.');
        }

        $this->empresaUsuarioModel->alterarCargo((int) $loggedUser->empresa_id, $targetUserId, $novoCargo);

        return $this->respond([
            'status'  => 200,
            'message' => "Cargo do colaborador atualizado para '{$novoCargo}' com sucesso!",
        ]);
    }
}
