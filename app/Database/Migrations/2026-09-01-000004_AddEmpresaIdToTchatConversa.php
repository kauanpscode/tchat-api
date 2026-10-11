<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEmpresaIdToTchatConversa extends Migration
{
    public function up()
    {
        $this->forge->addColumn('tchat_conversa', [
            'empresa_id' => [
                'type' => 'INT',
                'null' => true,
            ],
        ]);

        $this->db->query('ALTER TABLE tchat_conversa ADD CONSTRAINT fk_tchat_conversa_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE;');
        $this->db->query('CREATE INDEX idx_tchat_conversa_empresa ON tchat_conversa(empresa_id, ativo);');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE tchat_conversa DROP CONSTRAINT IF EXISTS fk_tchat_conversa_empresa;');
        $this->db->query('DROP INDEX IF EXISTS idx_tchat_conversa_empresa;');
        $this->forge->dropColumn('tchat_conversa', 'empresa_id');
    }
}
