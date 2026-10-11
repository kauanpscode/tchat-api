<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateEmpresaUsuariosTable extends Migration
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
            'usuario_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'cargo' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'colaborador', // admin, gerente, colaborador
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'ativo', // ativo, inativo, suspenso
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
        $this->forge->addUniqueKey(['empresa_id', 'usuario_id']);
        $this->forge->addForeignKey('empresa_id', 'empresas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('usuario_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('empresa_usuarios', true);
    }

    public function down()
    {
        $this->forge->dropTable('empresa_usuarios', true);
    }
}
