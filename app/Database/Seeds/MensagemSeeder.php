<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MensagemSeeder extends Seeder
{
    public function run()
    {
        $this->db->query('TRUNCATE TABLE tchat_mensagem CASCADE;');

        $baseTime = time() - (3600 * 24); // 1 dia atrás

        $mensagens = [
            // Conversa 1: Ana & Bruno
            [
                'id'             => 1,
                'id_conversa'    => 1,
                'id_usuario'     => 1,
                'tipo'           => 'texto',
                'mensagem'       => 'Olá Bruno, tudo bem?',
                'respondendo_id' => null,
                'client_id'      => 'client-uuid-001',
                'criado_em'      => date('Y-m-d H:i:s', $baseTime),
            ],
            [
                'id'             => 2,
                'id_conversa'    => 1,
                'id_usuario'     => 2,
                'tipo'           => 'texto',
                'mensagem'       => 'Oi Ana! Tudo ótimo por aqui e com você?',
                'respondendo_id' => null,
                'client_id'      => 'client-uuid-002',
                'criado_em'      => date('Y-m-d H:i:s', $baseTime + 300),
            ],
            [
                'id'             => 3,
                'id_conversa'    => 1,
                'id_usuario'     => 1,
                'tipo'           => 'texto',
                'mensagem'       => 'Tudo certo! Você conseguiu testar a API do TChat?',
                'respondendo_id' => null,
                'client_id'      => 'client-uuid-003',
                'criado_em'      => date('Y-m-d H:i:s', $baseTime + 600),
            ],
            [
                'id'             => 4,
                'id_conversa'    => 1,
                'id_usuario'     => 2,
                'tipo'           => 'texto',
                'mensagem'       => 'Sim! Ficou excelente, as seeds ajudaram bastante no teste.',
                'respondendo_id' => 3,
                'client_id'      => 'client-uuid-004',
                'criado_em'      => date('Y-m-d H:i:s', $baseTime + 900),
            ],

            // Conversa 2: Ana & Carla
            [
                'id'             => 5,
                'id_conversa'    => 2,
                'id_usuario'     => 1,
                'tipo'           => 'texto',
                'mensagem'       => 'Oi Carla, você tem o documento com os requisitos?',
                'respondendo_id' => null,
                'client_id'      => 'client-uuid-005',
                'criado_em'      => date('Y-m-d H:i:s', $baseTime + 1200),
            ],
            [
                'id'             => 6,
                'id_conversa'    => 2,
                'id_usuario'     => 3,
                'tipo'           => 'arquivo',
                'mensagem'       => 'relatorio_atividades.pdf',
                'respondendo_id' => 5,
                'client_id'      => 'client-uuid-006',
                'criado_em'      => date('Y-m-d H:i:s', $baseTime + 1500),
            ],

            // Conversa 3: Grupo Projeto TChat Mobile
            [
                'id'             => 7,
                'id_conversa'    => 3,
                'id_usuario'     => 1,
                'tipo'           => 'texto',
                'mensagem'       => 'Pessoal, bem-vindos ao grupo do Projeto TChat Mobile! 🚀',
                'respondendo_id' => null,
                'client_id'      => 'client-uuid-007',
                'criado_em'      => date('Y-m-d H:i:s', $baseTime + 1800),
            ],
            [
                'id'             => 8,
                'id_conversa'    => 3,
                'id_usuario'     => 2,
                'tipo'           => 'texto',
                'mensagem'       => 'Valeu Ana! Animado para começar as integrações.',
                'respondendo_id' => null,
                'client_id'      => 'client-uuid-008',
                'criado_em'      => date('Y-m-d H:i:s', $baseTime + 2100),
            ],
            [
                'id'             => 9,
                'id_conversa'    => 3,
                'id_usuario'     => 3,
                'tipo'           => 'texto',
                'mensagem'       => 'Show! Vamos pra cima.',
                'respondendo_id' => null,
                'client_id'      => 'client-uuid-009',
                'criado_em'      => date('Y-m-d H:i:s', $baseTime + 2400),
            ],
            [
                'id'             => 10,
                'id_conversa'    => 3,
                'id_usuario'     => 4,
                'tipo'           => 'texto',
                'mensagem'       => 'Qual a prioridade dos endpoints hoje?',
                'respondendo_id' => null,
                'client_id'      => 'client-uuid-010',
                'criado_em'      => date('Y-m-d H:i:s', $baseTime + 2700),
            ],
            [
                'id'             => 11,
                'id_conversa'    => 3,
                'id_usuario'     => 1,
                'tipo'           => 'texto',
                'mensagem'       => 'Foco no Login e na Listagem de Conversas!',
                'respondendo_id' => 10,
                'client_id'      => 'client-uuid-011',
                'criado_em'      => date('Y-m-d H:i:s', $baseTime + 3000),
            ],

            // Conversa 4: Grupo Suporte & Ops
            [
                'id'             => 12,
                'id_conversa'    => 4,
                'id_usuario'     => 2,
                'tipo'           => 'texto',
                'mensagem'       => 'Aviso: Manutenção programada no servidor às 22h.',
                'respondendo_id' => null,
                'client_id'      => 'client-uuid-012',
                'criado_em'      => date('Y-m-d H:i:s', $baseTime + 3300),
            ],
            [
                'id'             => 13,
                'id_conversa'    => 4,
                'id_usuario'     => 5,
                'tipo'           => 'texto',
                'mensagem'       => 'Ciente! Obrigado pelo aviso Bruno.',
                'respondendo_id' => null,
                'client_id'      => 'client-uuid-013',
                'criado_em'      => date('Y-m-d H:i:s', $baseTime + 3600),
            ],
        ];

        $this->db->table('tchat_mensagem')->insertBatch($mensagens);

        // Atualiza a sequence do PostgreSQL
        $this->db->query("SELECT setval(pg_get_serial_sequence('tchat_mensagem', 'id'), (SELECT MAX(id) FROM tchat_mensagem));");
    }
}
