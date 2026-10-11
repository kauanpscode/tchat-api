<?php

namespace App\Models;

use CodeIgniter\Model;

class PresencaModel extends Model
{
    protected $table = 'tchat_presenca';
    protected $primaryKey = 'id_usuario';
    protected $allowedFields = ['id_usuario', 'status', 'ultima_atividade_em'];
    protected $useTimestamps = false;

    /**
     * Atualiza o status do usuário para online e renova o batimento cardíaco (ping)
     */
    public function ping(int $userId): bool
    {
        $sql = "INSERT INTO tchat_presenca (id_usuario, status, ultima_atividade_em)
                VALUES (?, 'online', NOW())
                ON CONFLICT (id_usuario) 
                DO UPDATE SET status = 'online', ultima_atividade_em = NOW()";

        $this->db->query($sql, [$userId]);
        return $this->db->affectedRows() > 0;
    }

    /**
     * Define o usuário explicitamente como offline (ao fechar o app / logout)
     */
    public function setOffline(int $userId): bool
    {
        $sql = "INSERT INTO tchat_presenca (id_usuario, status, ultima_atividade_em)
                VALUES (?, 'offline', NOW())
                ON CONFLICT (id_usuario) 
                DO UPDATE SET status = 'offline', ultima_atividade_em = NOW()";

        $this->db->query($sql, [$userId]);
        return $this->db->affectedRows() > 0;
    }

    /**
     * Consulta o status de presença de um usuário específico
     * Se a última atividade foi há mais de 3 minutos, considera offline automaticamente.
     */
    public function getStatus(int $userId): array
    {
        $sql = "SELECT id_usuario, status, ultima_atividade_em,
                       CASE 
                           WHEN status = 'online' AND ultima_atividade_em >= (NOW() - INTERVAL '3 minutes') THEN 'online'
                           ELSE 'offline'
                       END AS status_real
                FROM tchat_presenca
                WHERE id_usuario = ?
                LIMIT 1";

        $query = $this->db->query($sql, [$userId]);
        $row = $query->getRowArray();

        if (!$row) {
            return [
                'id_usuario'          => $userId,
                'status'              => 'offline',
                'ultima_atividade_em' => null,
            ];
        }

        return [
            'id_usuario'          => (int) $row['id_usuario'],
            'status'              => $row['status_real'],
            'ultima_atividade_em' => $row['ultima_atividade_em'],
        ];
    }
}
