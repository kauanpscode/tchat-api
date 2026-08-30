<?php

namespace App\Models;

use CodeIgniter\Model;

class ConversaModel extends Model
{
    public function getConversas(string $phone): ?array
    {
        $sql = "SELECT id, name, phone, password, avatar_url, created_at, updated_at 
                FROM users 
                WHERE phone = ? 
                LIMIT 1";

        $query = $this->db->query($sql, [$phone]);
        $row = $query->getRowArray();

        return $row ?: null;
    }
}