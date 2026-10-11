<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class EmpresaUsuarioSeeder extends Seeder
{
    public function run()
    {
        $this->db->query('TRUNCATE TABLE empresa_usuarios CASCADE;');

        $now = date('Y-m-d H:i:s');

        $vinculos = [
            // Empresa 1: TechCorp
            [
                'id'            => 1,
                'empresa_id'    => 1,
                'usuario_id'    => 1, // Ana
                'cargo'         => 'admin',
                'status'        => 'ativo',
                'criado_em'     => $now,
                'atualizado_em' => $now,
            ],
            [
                'id'            => 2,
                'empresa_id'    => 1,
                'usuario_id'    => 2, // Bruno
                'cargo'         => 'colaborador',
                'status'        => 'ativo',
                'criado_em'     => $now,
                'atualizado_em' => $now,
            ],
            [
                'id'            => 3,
                'empresa_id'    => 1,
                'usuario_id'    => 3, // Carla
                'cargo'         => 'colaborador',
                'status'        => 'ativo',
                'criado_em'     => $now,
                'atualizado_em' => $now,
            ],

            // Empresa 2: InovaLog
            [
                'id'            => 4,
                'empresa_id'    => 2,
                'usuario_id'    => 4, // Diego
                'cargo'         => 'admin',
                'status'        => 'ativo',
                'criado_em'     => $now,
                'atualizado_em' => $now,
            ],
            [
                'id'            => 5,
                'empresa_id'    => 2,
                'usuario_id'    => 5, // Elena
                'cargo'         => 'colaborador',
                'status'        => 'ativo',
                'criado_em'     => $now,
                'atualizado_em' => $now,
            ],
        ];

        $this->db->table('empresa_usuarios')->insertBatch($vinculos);
        $this->db->query("SELECT setval(pg_get_serial_sequence('empresa_usuarios', 'id'), (SELECT MAX(id) FROM empresa_usuarios));");
    }
}
