<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateTchatPresenca extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_usuario' => [
                'type' => 'INT',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'offline', // online, ausente, offline
            ],
            'ultima_atividade_em' => [
                'type'    => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id_usuario', true);
        $this->forge->createTable('tchat_presenca', true);
    }

    public function down()
    {
        $this->forge->dropTable('tchat_presenca', true);
    }
}