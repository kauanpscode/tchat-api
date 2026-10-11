<?php

namespace App\Models;

use CodeIgniter\Model;

class EmpresaModel extends Model
{
    protected $table = 'empresas';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'nome',
        'nome_fantasia',
        'cnpj',
        'logo_url',
        'cor_primaria',
        'cor_secundaria',
        'ativo',
        'configuracoes',
        'criado_em',
        'atualizado_em',
    ];
    protected $useTimestamps = false;

    /**
     * Busca empresa por ID
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT id, nome, nome_fantasia, cnpj, logo_url, cor_primaria, cor_secundaria, ativo, configuracoes, criado_em, atualizado_em
                FROM empresas
                WHERE id = ?
                LIMIT 1";

        $query = $this->db->query($sql, [$id]);
        $row = $query->getRowArray();

        if ($row && !empty($row['configuracoes'])) {
            $row['configuracoes'] = json_decode($row['configuracoes'], true);
        }

        return $row ?: null;
    }

    /**
     * Busca empresa por CNPJ
     */
    public function findByCnpj(string $cnpj): ?array
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);

        $sql = "SELECT id, nome, nome_fantasia, cnpj, logo_url, cor_primaria, cor_secundaria, ativo
                FROM empresas
                WHERE cnpj = ?
                LIMIT 1";

        $query = $this->db->query($sql, [$cnpj]);
        return $query->getRowArray() ?: null;
    }

    /**
     * Cria nova empresa
     */
    public function createEmpresa(array $data): int
    {
        $sql = "INSERT INTO empresas (nome, nome_fantasia, cnpj, logo_url, cor_primaria, cor_secundaria, ativo, configuracoes, criado_em, atualizado_em)
                VALUES (?, ?, ?, ?, ?, ?, true, ?, NOW(), NOW())
                RETURNING id";

        $configJson = isset($data['configuracoes']) ? json_encode($data['configuracoes']) : null;

        $query = $this->db->query($sql, [
            $data['nome'],
            $data['nome_fantasia'] ?? null,
            !empty($data['cnpj']) ? preg_replace('/\D/', '', $data['cnpj']) : null,
            $data['logo_url'] ?? null,
            $data['cor_primaria'] ?? '#075E54',
            $data['cor_secundaria'] ?? '#128C7E',
            $configJson,
        ]);

        $row = $query->getRowArray();
        return (int) $row['id'];
    }

    /**
     * Atualiza identidade visual e configurações da empresa
     */
    public function updateBranding(int $empresaId, array $data): bool
    {
        $fields = [];
        $params = [];

        if (isset($data['nome'])) {
            $fields[] = 'nome = ?';
            $params[] = $data['nome'];
        }
        if (isset($data['nome_fantasia'])) {
            $fields[] = 'nome_fantasia = ?';
            $params[] = $data['nome_fantasia'];
        }
        if (isset($data['logo_url'])) {
            $fields[] = 'logo_url = ?';
            $params[] = $data['logo_url'];
        }
        if (isset($data['cor_primaria'])) {
            $fields[] = 'cor_primaria = ?';
            $params[] = $data['cor_primaria'];
        }
        if (isset($data['cor_secundaria'])) {
            $fields[] = 'cor_secundaria = ?';
            $params[] = $data['cor_secundaria'];
        }
        if (isset($data['configuracoes'])) {
            $fields[] = 'configuracoes = ?';
            $params[] = json_encode($data['configuracoes']);
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = 'atualizado_em = NOW()';
        $params[] = $empresaId;

        $sql = "UPDATE empresas SET " . implode(', ', $fields) . " WHERE id = ?";
        $this->db->query($sql, $params);

        return $this->db->affectedRows() > 0;
    }
}
