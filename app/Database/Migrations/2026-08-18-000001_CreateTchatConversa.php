<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateTchatConversa extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'auto_increment' => true,
            ],
            'tipo' => [
                'type'       => 'VARCHAR',
                'constraint' => 1,
                'default'    => 'P', // P: Privada, G: Grupo, D: Departamento
            ],
            'nome' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'chave_privada' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'unique'     => true,
            ],
            'id_grupo' => [
                'type'       => 'INT',
                'null'       => true,
            ],
            'id_proprietario' => [
                'type'       => 'INT',
                'null'       => true,
            ],
            'ultima_mensagem_id' => [
                'type'       => 'INT',
                'null'       => true,
            ],
            'ultima_atividade_em' => [
                'type'    => 'TIMESTAMP',
                'null'    => true,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'grupo_privado' => [
                'type'    => 'BOOLEAN',
                'default' => false,
            ],
            'descricao' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'regras' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'imagem' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'encerrada_em' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'encerrada_por' => [
                'type' => 'INT',
                'null' => true,
            ],
            'excluida_em' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'excluida_por' => [
                'type' => 'INT',
                'null' => true,
            ],
            'exclusao_motivo' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'criado_por' => [
                'type' => 'INT',
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
            'ativo' => [
                'type'    => 'BOOLEAN',
                'default' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('tchat_conversa', true);
    }

    public function down()
    {
        $this->forge->dropTable('tchat_conversa', true);
    }
}