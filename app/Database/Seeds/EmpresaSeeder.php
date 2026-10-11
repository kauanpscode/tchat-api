<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class EmpresaSeeder extends Seeder
{
    public function run()
    {
        $this->db->query('TRUNCATE TABLE empresas CASCADE;');

        $now = date('Y-m-d H:i:s');

        $empresas = [
            [
                'id'             => 1,
                'nome'           => 'TechCorp Soluções',
                'nome_fantasia'  => 'TechCorp',
                'cnpj'           => '12345678000199',
                'logo_url'       => 'https://images.unsplash.com/photo-1599305445671-ac291c95aaa9?w=150',
                'cor_primaria'   => '#0A4D68',
                'cor_secundaria' => '#088395',
                'ativo'          => true,
                'configuracoes'  => json_encode(['notificacoes_email' => true, 'permite_anexos' => true]),
                'criado_em'      => $now,
                'atualizado_em'  => $now,
            ],
            [
                'id'             => 2,
                'nome'           => 'InovaLog Logística',
                'nome_fantasia'  => 'InovaLog',
                'cnpj'           => '98765432000111',
                'logo_url'       => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=150',
                'cor_primaria'   => '#1E5128',
                'cor_secundaria' => '#4E9F3D',
                'ativo'          => true,
                'configuracoes'  => json_encode(['notificacoes_email' => false, 'permite_anexos' => true]),
                'criado_em'      => $now,
                'atualizado_em'  => $now,
            ],
        ];

        $this->db->table('empresas')->insertBatch($empresas);
        $this->db->query("SELECT setval(pg_get_serial_sequence('empresas', 'id'), (SELECT MAX(id) FROM empresas));");
    }
}
