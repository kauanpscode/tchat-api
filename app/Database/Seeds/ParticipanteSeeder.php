<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ParticipanteSeeder extends Seeder
{
    public function run()
    {
        $this->db->query('TRUNCATE TABLE tchat_participante CASCADE;');

        $now = date('Y-m-d H:i:s');

        $participantes = [
            // Conversa 1: Ana (1) e Bruno (2)
            [
                'id_conversa'             => 1,
                'id_usuario'              => 1,
                'mensagem_inicial_id'     => 0,
                'ultima_mensagem_lida_id' => 4,
                'entrou_em'               => $now,
                'ativo'                   => true,
                'administrador'           => false,
                'silenciado'              => false,
                'arquivado'               => false,
                'fixada'                  => true,
                'vinculo_manual'          => false,
                'atualizado_em'           => $now,
            ],
            [
                'id_conversa'             => 1,
                'id_usuario'              => 2,
                'mensagem_inicial_id'     => 0,
                'ultima_mensagem_lida_id' => 4,
                'entrou_em'               => $now,
                'ativo'                   => true,
                'administrador'           => false,
                'silenciado'              => false,
                'arquivado'               => false,
                'fixada'                  => false,
                'vinculo_manual'          => false,
                'atualizado_em'           => $now,
            ],

            // Conversa 2: Ana (1) e Carla (3)
            [
                'id_conversa'             => 2,
                'id_usuario'              => 1,
                'mensagem_inicial_id'     => 0,
                'ultima_mensagem_lida_id' => 6,
                'entrou_em'               => $now,
                'ativo'                   => true,
                'administrador'           => false,
                'silenciado'              => false,
                'arquivado'               => false,
                'fixada'                  => false,
                'vinculo_manual'          => false,
                'atualizado_em'           => $now,
            ],
            [
                'id_conversa'             => 2,
                'id_usuario'              => 3,
                'mensagem_inicial_id'     => 0,
                'ultima_mensagem_lida_id' => 6,
                'entrou_em'               => $now,
                'ativo'                   => true,
                'administrador'           => false,
                'silenciado'              => false,
                'arquivado'               => false,
                'fixada'                  => false,
                'vinculo_manual'          => false,
                'atualizado_em'           => $now,
            ],

            // Conversa 3: Grupo Projeto TChat Mobile
            [
                'id_conversa'             => 3,
                'id_usuario'              => 1,
                'mensagem_inicial_id'     => 0,
                'ultima_mensagem_lida_id' => 11,
                'entrou_em'               => $now,
                'ativo'                   => true,
                'administrador'           => true,
                'silenciado'              => false,
                'arquivado'               => false,
                'fixada'                  => true,
                'vinculo_manual'          => false,
                'atualizado_em'           => $now,
            ],
            [
                'id_conversa'             => 3,
                'id_usuario'              => 2,
                'mensagem_inicial_id'     => 0,
                'ultima_mensagem_lida_id' => 11,
                'entrou_em'               => $now,
                'ativo'                   => true,
                'administrador'           => false,
                'silenciado'              => false,
                'arquivado'               => false,
                'fixada'                  => false,
                'vinculo_manual'          => false,
                'atualizado_em'           => $now,
            ],
            [
                'id_conversa'             => 3,
                'id_usuario'              => 3,
                'mensagem_inicial_id'     => 0,
                'ultima_mensagem_lida_id' => 10,
                'entrou_em'               => $now,
                'ativo'                   => true,
                'administrador'           => false,
                'silenciado'              => false,
                'arquivado'               => false,
                'fixada'                  => false,
                'vinculo_manual'          => false,
                'atualizado_em'           => $now,
            ],
            [
                'id_conversa'             => 3,
                'id_usuario'              => 4,
                'mensagem_inicial_id'     => 0,
                'ultima_mensagem_lida_id' => 11,
                'entrou_em'               => $now,
                'ativo'                   => true,
                'administrador'           => false,
                'silenciado'              => false,
                'arquivado'               => false,
                'fixada'                  => false,
                'vinculo_manual'          => false,
                'atualizado_em'           => $now,
            ],

            // Conversa 4: Grupo Suporte & Ops
            [
                'id_conversa'             => 4,
                'id_usuario'              => 2,
                'mensagem_inicial_id'     => 0,
                'ultima_mensagem_lida_id' => 13,
                'entrou_em'               => $now,
                'ativo'                   => true,
                'administrador'           => true,
                'silenciado'              => false,
                'arquivado'               => false,
                'fixada'                  => false,
                'vinculo_manual'          => false,
                'atualizado_em'           => $now,
            ],
            [
                'id_conversa'             => 4,
                'id_usuario'              => 5,
                'mensagem_inicial_id'     => 0,
                'ultima_mensagem_lida_id' => 13,
                'entrou_em'               => $now,
                'ativo'                   => true,
                'administrador'           => false,
                'silenciado'              => false,
                'arquivado'               => false,
                'fixada'                  => false,
                'vinculo_manual'          => false,
                'atualizado_em'           => $now,
            ],
        ];

        $this->db->table('tchat_participante')->insertBatch($participantes);
    }
}
