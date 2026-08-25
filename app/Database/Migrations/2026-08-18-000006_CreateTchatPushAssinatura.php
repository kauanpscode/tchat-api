<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateTchatPushAssinatura extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'auto_increment' => true,
            ],
            'id_usuario' => [
                'type' => 'INT',
            ],
            'device_token' => [
                'type' => 'TEXT',
            ],
            'plataforma' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'android', // android, ios, web
            ],
            'criado_em' => [
                'type'    => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'atualizado_em' => [
                'type'    => 'TIMESTAMP',
                'null'    => true,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('tchat_push_assinatura', true);
    }

    public function down()
    {
        $this->forge->dropTable('tchat_push_assinatura', true);
    }
}