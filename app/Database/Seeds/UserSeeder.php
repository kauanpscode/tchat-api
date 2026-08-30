<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Limpa a tabela antes de popular
        $this->db->query('TRUNCATE TABLE users CASCADE;');

        $defaultPassword = password_hash('123456', PASSWORD_BCRYPT);
        $now = date('Y-m-d H:i:s');

        $users = [
            [
                'id'         => 1,
                'name'       => 'Ana Silva',
                'phone'      => '5511999990001',
                'password'   => $defaultPassword,
                'avatar_url' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id'         => 2,
                'name'       => 'Bruno Souza',
                'phone'      => '5511999990002',
                'password'   => $defaultPassword,
                'avatar_url' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id'         => 3,
                'name'       => 'Carla Mendes',
                'phone'      => '5511999990003',
                'password'   => $defaultPassword,
                'avatar_url' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=150',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id'         => 4,
                'name'       => 'Diego Santos',
                'phone'      => '5511999990004',
                'password'   => $defaultPassword,
                'avatar_url' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id'         => 5,
                'name'       => 'Elena Rocha',
                'phone'      => '5511999990005',
                'password'   => $defaultPassword,
                'avatar_url' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $this->db->table('users')->insertBatch($users);

        // Atualiza a sequence do PostgreSQL para evitar conflitos nos próximos IDs
        $this->db->query("SELECT setval(pg_get_serial_sequence('users', 'id'), (SELECT MAX(id) FROM users));");
    }
}
