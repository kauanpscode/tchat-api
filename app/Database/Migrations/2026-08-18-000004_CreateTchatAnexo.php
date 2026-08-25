<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateTchatAnexo extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'auto_increment' => true,
            ],
            'id_mensagem' => [
                'type' => 'INT',
            ],
            'tipo' => [
                'type'       => 'VARCHAR',
                'constraint' => 20, // imagem, pdf, audio, documento
            ],
            'nome_original' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'caminho' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'tamanho' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'criado_em' => [
                'type'    => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'excluido_em' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('id_mensagem', 'tchat_mensagem', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tchat_anexo', true);
    }

    public function down()
    {
        $this->forge->dropTable('tchat_anexo', true);
    }
}