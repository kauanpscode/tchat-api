<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PushAssinaturaSeeder extends Seeder
{
    public function run()
    {
        $this->db->query('TRUNCATE TABLE tchat_push_assinatura CASCADE;');

        $now = date('Y-m-d H:i:s');

        $assinaturas = [
            [
                'id'            => 1,
                'id_usuario'    => 1,
                'device_token'  => 'ExponentPushToken[SampleTokenAna123456]',
                'plataforma'    => 'android',
                'criado_em'     => $now,
                'atualizado_em' => $now,
            ],
            [
                'id'            => 2,
                'id_usuario'    => 2,
                'device_token'  => 'ExponentPushToken[SampleTokenBruno654321]',
                'plataforma'    => 'ios',
                'criado_em'     => $now,
                'atualizado_em' => $now,
            ],
        ];

        $this->db->table('tchat_push_assinatura')->insertBatch($assinaturas);

        // Atualiza a sequence do PostgreSQL
        $this->db->query("SELECT setval(pg_get_serial_sequence('tchat_push_assinatura', 'id'), (SELECT MAX(id) FROM tchat_push_assinatura));");
    }
}
