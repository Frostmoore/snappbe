<?php

namespace Database\Seeders;

use App\Models\AccessLevel;
use Illuminate\Database\Seeder;

/**
 * Livelli di accesso iniziali (placeholder agnostici, D4).
 * Da mappare ai livelli reali del sito WordPress SNA quando saranno noti.
 */
class AccessLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['key' => 'public', 'label' => 'Pubblico', 'weight' => 0],
            ['key' => 'iscritto', 'label' => 'Iscritto', 'weight' => 10],
            ['key' => 'premium', 'label' => 'Premium', 'weight' => 20],
        ];

        foreach ($levels as $level) {
            AccessLevel::updateOrCreate(['key' => $level['key']], $level);
        }
    }
}
