<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    /**
     * Busca um usuário pelo número de telefone
     */
    public function findByPhone(string $phone): ?array
    {
        $phone = onlyNumbers($phone);

        $sql = "SELECT id, name, phone, password, avatar_url, created_at, updated_at 
                FROM users 
                WHERE phone = ? 
                LIMIT 1";

        $query = $this->db->query($sql, [$phone]);
        $row = $query->getRowArray();

        return $row ?: null;
    }

    /**
     * Busca um usuário pelo ID
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT id, name, phone, avatar_url, created_at, updated_at 
                FROM users 
                WHERE id = ? 
                LIMIT 1";

        $query = $this->db->query($sql, [$id]);
        $row = $query->getRowArray();

        return $row ?: null;
    }

    /**
     * Insere um novo usuário e retorna o ID gerado (via RETURNING id do PostgreSQL)
     */
    public function createRaw(array $data): int
    {
        $data['phone'] = onlyNumbers($data['phone']);

        $sql = "INSERT INTO users (name, phone, password, created_at, updated_at) 
                VALUES (?, ?, ?, NOW(), NOW()) 
                RETURNING id";

        // Gera o hash seguro da senha com Bcrypt
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);

        $query = $this->db->query($sql, [
            $data['name'],
            $data['phone'],
            $passwordHash
        ]);

        $result = $query->getRowArray();
        return (int) $result['id'];
    }

    /**
     * Verifica se o telefone já existe no banco
     */
    public function phoneExists(string $phone): bool
    {
        $phone = onlyNumbers($phone);
        
        $sql = "SELECT 1 FROM users WHERE phone = ? LIMIT 1";
        $query = $this->db->query($sql, [$phone]);
        
        return $query->getNumRows() > 0;
    }

    /**
     * Atualiza a foto de perfil (avatar)
     */
    public function updateAvatar(int $userId, string $avatarUrl): bool
    {
        $sql = "UPDATE users 
                SET avatar_url = ?, updated_at = NOW() 
                WHERE id = ?";

        $this->db->query($sql, [$avatarUrl, $userId]);
        return $this->db->affectedRows() > 0;
    }
}