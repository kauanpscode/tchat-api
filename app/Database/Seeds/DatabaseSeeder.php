<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call('UserSeeder');
        $this->call('ConversaSeeder');
        $this->call('ParticipanteSeeder');
        $this->call('MensagemSeeder');
        $this->call('AnexoSeeder');
        $this->call('PresencaSeeder');
        $this->call('PushAssinaturaSeeder');
    }
}
