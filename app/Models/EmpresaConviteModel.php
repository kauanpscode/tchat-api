<?php

namespace App\Models;

use CodeIgniter\Model;

class EmpresaConviteModel extends Model
{
    protected $table = 'empresa_convites';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'empresa_id',
        'email',
        'telefone',
        'token',
        'cargo',
        'status',
        'criado_por',
        'expira_em',
        'criado_em',
    ];
    protected $useTimestamps = false;

    /**
     * Cria um novo convite com token seguro de 64 caracteres
     */
    public function criarConvite(int $empresaId, int $criadoPor, ?string $email, ?string $telefone, string $cargo = 'colaborador'): array
    {
        $token = bin2hex(random_bytes(32));
        $expiraEm = date('Y-m-d H:i:s', strtotime('+7 days'));

        $sql = "INSERT INTO empresa_convites (empresa_id, email, telefone, token, cargo, status, criado_por, expira_em, criado_em)
                VALUES (?, ?, ?, ?, ?, 'pendente', ?, ?, NOW())
                RETURNING id";

        $query = $this->db->query($sql, [
            $empresaId,
            $email,
            $telefone ? preg_replace('/\D/', '', $telefone) : null,
            $token,
            $cargo,
            $criadoPor,
            $expiraEm,
        ]);

        $row = $query->getRowArray();

        return [
            'id'         => (int) $row['id'],
            'token'      => $token,
            'cargo'      => $cargo,
            'expira_em'  => $expiraEm,
            'status'     => 'pendente',
        ];
    }

    /**
     * Busca convite pelo token e verifica validade
     */
    public function buscarPorToken(string $token): ?array
    {
        $sql = "SELECT c.id, c.empresa_id, c.email, c.telefone, c.token, c.cargo, c.status, c.expira_em, c.criado_em,
                       e.nome AS empresa_nome, e.logo_url AS empresa_logo, e.cor_primaria AS empresa_cor_primaria,
                       u.name AS convidado_por_nome
                FROM empresa_convites c
                INNER JOIN empresas e ON e.id = c.empresa_id
                INNER JOIN users u ON u.id = c.criado_por
                WHERE c.token = ?
                LIMIT 1";

        $query = $this->db->query($sql, [$token]);
        $convite = $query->getRowArray();

        if (!$convite) {
            return null;
        }

        // Verifica se expirou
        if (strtotime($convite['expira_em']) < time() && $convite['status'] === 'pendente') {
            $this->db->query("UPDATE empresa_convites SET status = 'expirado' WHERE id = ?", [$convite['id']]);
            $convite['status'] = 'expirado';
        }

        return $convite;
    }

    /**
     * Lista todos os convites de uma empresa
     */
    public function listarConvites(int $empresaId): array
    {
        $sql = "SELECT c.id, c.email, c.telefone, c.token, c.cargo, c.status, c.expira_em, c.criado_em,
                       u.name AS criado_por_nome
                FROM empresa_convites c
                INNER JOIN users u ON u.id = c.criado_por
                WHERE c.empresa_id = ?
                ORDER BY c.criado_em DESC";

        $query = $this->db->query($sql, [$empresaId]);
        return $query->getResultArray();
    }

    /**
     * Cancela um convite pendente
     */
    public function cancelarConvite(int $conviteId, int $empresaId): bool
    {
        $sql = "UPDATE empresa_convites 
                SET status = 'cancelado' 
                WHERE id = ? AND empresa_id = ? AND status = 'pendente'";

        $this->db->query($sql, [$conviteId, $empresaId]);
        return $this->db->affectedRows() > 0;
    }

    /**
     * Marca convite como aceito
     */
    public function marcarComoAceito(int $conviteId): bool
    {
        $sql = "UPDATE empresa_convites 
                SET status = 'aceito' 
                WHERE id = ?";

        $this->db->query($sql, [$conviteId]);
        return $this->db->affectedRows() > 0;
    }
}
