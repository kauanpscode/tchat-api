<?php

namespace App\Models;

use CodeIgniter\Model;

class PushModel extends Model
{
    protected $table = 'tchat_push_assinatura';
    protected $primaryKey = 'id';
    protected $allowedFields = ['id_usuario', 'device_token', 'plataforma', 'criado_em', 'atualizado_em'];
    protected $useTimestamps = false;

    /**
     * Registra ou atualiza o token de push do dispositivo (Expo / FCM) para o colaborador
     */
    public function salvarToken(int $userId, string $token, string $plataforma = 'android'): bool
    {
        $sql = "INSERT INTO tchat_push_assinatura (id_usuario, device_token, plataforma, criado_em, atualizado_em) 
                VALUES (?, ?, ?, NOW(), NOW()) 
                ON CONFLICT (id_usuario, device_token) 
                DO UPDATE SET plataforma = EXCLUDED.plataforma, atualizado_em = NOW()";

        $this->db->query($sql, [$userId, $token, $plataforma]);
        return $this->db->affectedRows() > 0;
    }

    /**
     * Remove o token de push (utilizado quando o webhook do Expo acusa token inválido/desinstalado)
     */
    public function removerToken(string $token): bool
    {
        $sql = "DELETE FROM tchat_push_assinatura WHERE device_token = ?";
        $this->db->query($sql, [$token]);
        return $this->db->affectedRows() > 0;
    }

    /**
     * Retorna a lista de tokens dos colaboradores especificados
     */
    public function getTokensByUserIds(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $sql = "SELECT device_token FROM tchat_push_assinatura WHERE id_usuario IN ({$placeholders})";

        $query = $this->db->query($sql, array_values($userIds));
        $rows = $query->getResultArray();

        $tokens = [];
        foreach ($rows as $row) {
            $tokens[] = $row['device_token'];
        }

        return $tokens;
    }
}
