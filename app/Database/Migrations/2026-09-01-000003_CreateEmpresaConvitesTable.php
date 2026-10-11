<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateEmpresaConvitesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'auto_increment' => true,
            ],
            'empresa_id' => [
                'type' => 'INT',
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'telefone' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'token' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'unique'     => true,
            ],
            'cargo' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'colaborador',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'pendente', // pendente, aceito, expirado, cancelado
            ],
            'criado_por' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'expira_em' => [
                'type' => 'TIMESTAMP',
            ],
            'criado_em' => [
                'type'    => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('empresa_id', 'empresas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('criado_por', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('empresa_convites', true);
    }

    public function down()
    {
        $this->forge->dropTable('empresa_convites', true);
    }
}
