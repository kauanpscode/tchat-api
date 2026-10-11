<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\PresencaModel;
use App\Models\EmpresaUsuarioModel;
use CodeIgniter\API\ResponseTrait;

/**
 * Controller de Status de Presença (Online / Visto por Último)
 */
class PresencaController extends BaseController
{
    use ResponseTrait;

    protected PresencaModel $presencaModel;
    protected EmpresaUsuarioModel $empresaUsuarioModel;

    public function __construct()
    {
        $this->presencaModel       = model(PresencaModel::class);
        $this->empresaUsuarioModel = model(EmpresaUsuarioModel::class);
    }

    /**
     * Envia o batimento periódico (Heartbeat/Ping) mantendo o usuário como Online
     * POST /api/presenca/ping (Requer AuthFilter)
     */
    public function ping()
    {
        $loggedUser = $this->request->user ?? null;

        if (!$loggedUser) {
            return $this->failUnauthorized('Usuário não autenticado.');
        }

        $this->presencaModel->ping((int) $loggedUser->id);

        return $this->respond([
            'status'  => 200,
            'message' => 'Presença renovada: Online',
        ]);
    }

    /**
     * Marca o usuário explicitamente como Offline (ao minimizar app ou fechar sessão)
     * POST /api/presenca/offline (Requer AuthFilter)
     */
    public function offline()
    {
        $loggedUser = $this->request->user ?? null;

        if (!$loggedUser) {
            return $this->failUnauthorized('Usuário não autenticado.');
        }

        $this->presencaModel->setOffline((int) $loggedUser->id);

        return $this->respond([
            'status'  => 200,
            'message' => 'Status atualizado: Offline',
        ]);
    }

    /**
     * Consulta o status de presença de outro colaborador da mesma empresa
     * GET /api/presenca/{userId} (Requer AuthFilter)
     */
    public function status($targetUserId = null)
    {
        $loggedUser = $this->request->user ?? null;

        if (!$loggedUser) {
            return $this->failUnauthorized('Usuário não autenticado.');
        }

        $targetUserId = (int) $targetUserId;

        // Valida se o colaborador alvo é membro da mesma empresa
        if (!$this->empresaUsuarioModel->isMembroAtivo((int) $loggedUser->empresa_id, $targetUserId)) {
            return $this->failForbidden('Acesso negado: Este colaborador não pertence à sua organização.');
        }

        $presenca = $this->presencaModel->getStatus($targetUserId);

        return $this->respond([
            'status' => 200,
            'data'   => $presenca,
        ]);
    }
}
