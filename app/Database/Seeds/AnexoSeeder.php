<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AnexoSeeder extends Seeder
{
    public function run()
    {
        $this->db->query('TRUNCATE TABLE tchat_anexo CASCADE;');

        $now = date('Y-m-d H:i:s');

        $anexos = [
            [
                'id'            => 1,
                'id_mensagem'   => 6,
                'tipo'          => 'pdf',
                'nome_original' => 'relatorio_atividades.pdf',
                'caminho'       => 'uploads/anexos/relatorio_atividades_2026.pdf',
                'tamanho'       => 102450,
                'criado_em'     => $now,
            ],
        ];

        $this->db->table('tchat_anexo')->insertBatch($anexos);

        // Atualiza a sequence do PostgreSQL
        $this->db->query("SELECT setval(pg_get_serial_sequence('tchat_anexo', 'id'), (SELECT MAX(id) FROM tchat_anexo));");
    }
}
