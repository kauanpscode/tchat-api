<?php

namespace App\Models;

use CodeIgniter\Model;

class PushModel extends Model
{
    protected $table = 'tchat_push_assinatura';
    protected $primaryKey = 'id';
    protected $allowedFields = ['id_usuario', 'device_token', 'plataforma', 'criado_em', 'atualizado_em'];
    protected $useTimestamps = true;
    protected $createdField  = 'criado_em';
    protected $updatedField  = 'atualizado_em';

    /**
     * Registra ou atualiza um token de push para um usuário
     */
    public function salvarToken(int $userId, string $token, string $plataforma = 'android')
    {
        $sql = "INSERT INTO tchat_push_assinatura (id_usuario, device_token, plataforma, criado_em, atualizado_em) 
                VALUES (?, ?, ?, NOW(), NOW()) 
                ON CONFLICT (id_usuario, device_token) 
                DO UPDATE SET atualizado_em = NOW()";

        return $this->db->query($sql, [$userId, $token, $plataforma]);
    }

    /**
     * Remove um token (útil para quando o webhoook do Expo acusa "NotRegistered")
     */
    public function removerToken(string $token)
    {
        $this->where('device_token', $token)->delete();
    }

    /**
     * Retorna os tokens de um array de usuários
     */
    public function getTokensByUserIds(array $userIds): array
    {
        if (empty($userIds)) return [];

        $query = $this->whereIn('id_usuario', $userIds)->get();
        $tokens = [];
        
        foreach ($query->getResultArray() as $row) {
            $tokens[] = $row['device_token'];
        }

        return $tokens;
    }
}
