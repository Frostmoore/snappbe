<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Crea (o aggiorna) l'utente super-admin di riferimento.
     * Idempotente: rieseguibile senza creare duplicati.
     *
     * Login: nbdy88@gmail.com / password "password".
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'nbdy88@gmail.com'],
            [
                'name' => 'smp-webmaster',
                'password' => 'password', // il cast 'hashed' la cifra automaticamente
                'role' => UserRole::SuperAdmin->value,
                'email_verified_at' => now(),
            ]
        );
    }
}
