<?php

namespace App\Models;

use CodeIgniter\Model;

class EmpresaUsuarioModel extends Model
{
    protected $table = 'empresa_usuarios';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'empresa_id',
        'usuario_id',
        'cargo',
        'status',
        'criado_em',
        'atualizado_em',
    ];
    protected $useTimestamps = false;

    /**
     * Obtém o vínculo ativo ou inativo entre usuário e empresa
     */
    public function getVinculo(int $usuarioId, int $empresaId): ?array
    {
        $sql = "SELECT eu.id, eu.empresa_id, eu.usuario_id, eu.cargo, eu.status,
                       e.nome AS empresa_nome, e.ativo AS empresa_ativa
                FROM empresa_usuarios eu
                INNER JOIN empresas e ON e.id = eu.empresa_id
                WHERE eu.usuario_id = ? AND eu.empresa_id = ?
                LIMIT 1";

        $query = $this->db->query($sql, [$usuarioId, $empresaId]);
        return $query->getRowArray() ?: null;
    }

    /**
     * Lista todos os colaboradores de uma empresa
     */
    public function getColaboradores(int $empresaId, bool $somenteAtivos = true): array
    {
        $statusClause = $somenteAtivos ? "AND eu.status = 'ativo'" : "";

        $sql = "SELECT u.id, u.name, u.phone, u.avatar_url,
                       eu.cargo, eu.status, eu.criado_em AS membro_desde,
                       COALESCE(p.status, 'offline') AS status_presenca
                FROM empresa_usuarios eu
                INNER JOIN users u ON u.id = eu.usuario_id
                LEFT JOIN tchat_presenca p ON p.id_usuario = u.id
                WHERE eu.empresa_id = ? {$statusClause}
                ORDER BY u.name ASC";

        $query = $this->db->query($sql, [$empresaId]);
        return $query->getResultArray();
    }

    /**
     * Adiciona um novo membro à empresa
     */
    public function adicionarMembro(int $empresaId, int $usuarioId, string $cargo = 'colaborador'): int
    {
        $sql = "INSERT INTO empresa_usuarios (empresa_id, usuario_id, cargo, status, criado_em, atualizado_em)
                VALUES (?, ?, ?, 'ativo', NOW(), NOW())
                ON CONFLICT (empresa_id, usuario_id) 
                DO UPDATE SET status = 'ativo', cargo = EXCLUDED.cargo, atualizado_em = NOW()
                RETURNING id";

        $query = $this->db->query($sql, [$empresaId, $usuarioId, $cargo]);
        $row = $query->getRowArray();
        return (int) $row['id'];
    }

    /**
     * Altera status do colaborador (ativo, inativo, suspenso)
     */
    public function alterarStatus(int $empresaId, int $usuarioId, string $status): bool
    {
        $sql = "UPDATE empresa_usuarios 
                SET status = ?, atualizado_em = NOW() 
                WHERE empresa_id = ? AND usuario_id = ?";

        $this->db->query($sql, [$status, $empresaId, $usuarioId]);
        return $this->db->affectedRows() > 0;
    }

    /**
     * Altera cargo do colaborador (admin, colaborador, etc.)
     */
    public function alterarCargo(int $empresaId, int $usuarioId, string $cargo): bool
    {
        $sql = "UPDATE empresa_usuarios 
                SET cargo = ?, atualizado_em = NOW() 
                WHERE empresa_id = ? AND usuario_id = ?";

        $this->db->query($sql, [$cargo, $empresaId, $usuarioId]);
        return $this->db->affectedRows() > 0;
    }

    /**
     * Verifica se o usuário é membro ativo da empresa
     */
    public function isMembroAtivo(int $empresaId, int $usuarioId): bool
    {
        $sql = "SELECT 1 FROM empresa_usuarios 
                WHERE empresa_id = ? AND usuario_id = ? AND status = 'ativo' 
                LIMIT 1";

        $query = $this->db->query($sql, [$empresaId, $usuarioId]);
        return $query->getNumRows() > 0;
    }

    /**
     * Verifica se o usuário é administrador da empresa
     */
    public function isAdmin(int $empresaId, int $usuarioId): bool
    {
        $sql = "SELECT 1 FROM empresa_usuarios 
                WHERE empresa_id = ? AND usuario_id = ? AND cargo = 'admin' AND status = 'ativo' 
                LIMIT 1";

        $query = $this->db->query($sql, [$empresaId, $usuarioId]);
        return $query->getNumRows() > 0;
    }

    /**
     * Retorna a lista de empresas das quais o usuário é membro ativo
     */
    public function getEmpresasDoUsuario(int $usuarioId): array
    {
        $sql = "SELECT e.id, e.nome, e.nome_fantasia, e.logo_url, e.cor_primaria, e.cor_secundaria,
                       eu.cargo, eu.status
                FROM empresa_usuarios eu
                INNER JOIN empresas e ON e.id = eu.empresa_id
                WHERE eu.usuario_id = ? AND eu.status = 'ativo' AND e.ativo = true
                ORDER BY e.id ASC";

        $query = $this->db->query($sql, [$usuarioId]);
        return $query->getResultArray();
    }
}
