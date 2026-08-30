<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PresencaSeeder extends Seeder
{
    public function run()
    {
        $this->db->query('TRUNCATE TABLE tchat_presenca CASCADE;');

        $now = date('Y-m-d H:i:s');

        $presencas = [
            [
                'id_usuario'          => 1,
                'status'              => 'online',
                'ultima_atividade_em' => $now,
            ],
            [
                'id_usuario'          => 2,
                'status'              => 'online',
                'ultima_atividade_em' => $now,
            ],
            [
                'id_usuario'          => 3,
                'status'              => 'ausente',
                'ultima_atividade_em' => date('Y-m-d H:i:s', strtotime('-15 minutes')),
            ],
            [
                'id_usuario'          => 4,
                'status'              => 'offline',
                'ultima_atividade_em' => date('Y-m-d H:i:s', strtotime('-2 hours')),
            ],
            [
                'id_usuario'          => 5,
                'status'              => 'offline',
                'ultima_atividade_em' => date('Y-m-d H:i:s', strtotime('-1 day')),
            ],
        ];

        $this->db->table('tchat_presenca')->insertBatch($presencas);
    }
}
