<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateTchatParticipante extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_conversa' => [
                'type' => 'INT',
            ],
            'id_usuario' => [
                'type' => 'INT',
            ],
            'mensagem_inicial_id' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'ultima_mensagem_lida_id' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'entrou_em' => [
                'type'    => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'saiu_em' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'ativo' => [
                'type'    => 'BOOLEAN',
                'default' => true,
            ],
            'administrador' => [
                'type'    => 'BOOLEAN',
                'default' => false,
            ],
            'silenciado' => [
                'type'    => 'BOOLEAN',
                'default' => false,
            ],
            'arquivado' => [
                'type'    => 'BOOLEAN',
                'default' => false,
            ],
            'fixada' => [
                'type'    => 'BOOLEAN',
                'default' => false,
            ],
            'vinculo_manual' => [
                'type'    => 'BOOLEAN',
                'default' => false,
            ],
            'atualizado_em' => [
                'type'    => 'TIMESTAMP',
                'null'    => true,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey(['id_conversa', 'id_usuario'], true);
        $this->forge->addForeignKey('id_conversa', 'tchat_conversa', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tchat_participante', true);
    }

    public function down()
    {
        $this->forge->dropTable('tchat_participante', true);
    }
}