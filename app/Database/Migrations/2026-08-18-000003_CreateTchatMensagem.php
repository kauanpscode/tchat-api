<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateTchatMensagem extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'auto_increment' => true,
            ],
            'id_conversa' => [
                'type' => 'INT',
            ],
            'id_usuario' => [
                'type' => 'INT',
            ],
            'tipo' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'texto', // texto, imagem, audio, arquivo
            ],
            'mensagem' => [
                'type' => 'TEXT',
            ],
            'respondendo_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'client_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => true,
            ],
            'encaminhada_de' => [
                'type' => 'INT',
                'null' => true,
            ],
            'criado_em' => [
                'type'    => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'editado_em' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'excluido_em' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('id_conversa', 'tchat_conversa', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('respondendo_id', 'tchat_mensagem', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('tchat_mensagem', true);
    }

    public function down()
    {
        $this->forge->dropTable('tchat_mensagem', true);
    }
}