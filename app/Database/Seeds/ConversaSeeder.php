<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ConversaSeeder extends Seeder
{
    public function run()
    {
        $this->db->query('TRUNCATE TABLE tchat_conversa CASCADE;');

        $now = date('Y-m-d H:i:s');

        $conversas = [
            [
                'id'                   => 1,
                'empresa_id'           => 1,
                'tipo'                 => 'P',
                'nome'                 => null,
                'chave_privada'        => '1_2',
                'id_grupo'             => null,
                'id_proprietario'      => null,
                'ultima_mensagem_id'   => 4,
                'ultima_atividade_em'  => $now,
                'grupo_privado'        => false,
                'descricao'            => null,
                'regras'               => null,
                'imagem'               => null,
                'criado_por'           => 1,
                'criado_em'            => $now,
                'atualizado_em'        => $now,
                'ativo'                => true,
            ],
            [
                'id'                   => 2,
                'empresa_id'           => 1,
                'tipo'                 => 'P',
                'nome'                 => null,
                'chave_privada'        => '1_3',
                'id_grupo'             => null,
                'id_proprietario'      => null,
                'ultima_mensagem_id'   => 6,
                'ultima_atividade_em'  => $now,
                'grupo_privado'        => false,
                'descricao'            => null,
                'regras'               => null,
                'imagem'               => null,
                'criado_por'           => 1,
                'criado_em'            => $now,
                'atualizado_em'        => $now,
                'ativo'                => true,
            ],
            [
                'id'                   => 3,
                'empresa_id'           => 1,
                'tipo'                 => 'G',
                'nome'                 => 'Projeto TChat Mobile',
                'chave_privada'        => null,
                'id_grupo'             => null,
                'id_proprietario'      => 1,
                'ultima_mensagem_id'   => 11,
                'ultima_atividade_em'  => $now,
                'grupo_privado'        => false,
                'descricao'            => 'Grupo oficial de desenvolvimento do aplicativo TChat Mobile.',
                'regras'               => 'Manter foco em assuntos do projeto TChat.',
                'imagem'               => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=150',
                'criado_por'           => 1,
                'criado_em'            => $now,
                'atualizado_em'        => $now,
                'ativo'                => true,
            ],
            [
                'id'                   => 4,
                'empresa_id'           => 2,
                'tipo'                 => 'G',
                'nome'                 => 'Equipe de Suporte & Ops',
                'chave_privada'        => null,
                'id_grupo'             => null,
                'id_proprietario'      => 4,
                'ultima_mensagem_id'   => 13,
                'ultima_atividade_em'  => $now,
                'grupo_privado'        => false,
                'descricao'            => 'Canal de alinhamento de infraestrutura e suporte.',
                'regras'               => 'Avisar com antecedência sobre manutenções.',
                'imagem'               => 'https://images.unsplash.com/photo-1556761175-5973dc0f32e7?w=150',
                'criado_por'           => 4,
                'criado_em'            => $now,
                'atualizado_em'        => $now,
                'ativo'                => true,
            ],
        ];

        $this->db->table('tchat_conversa')->insertBatch($conversas);

        // Atualiza a sequence do PostgreSQL
        $this->db->query("SELECT setval(pg_get_serial_sequence('tchat_conversa', 'id'), (SELECT MAX(id) FROM tchat_conversa));");
    }
}
