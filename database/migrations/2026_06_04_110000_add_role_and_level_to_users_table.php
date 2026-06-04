<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // role: governa l'accesso al pannello admin (superadmin|admin|staff|member).
            $table->string('role')->default('member')->after('email');
            // membership_level: livello ereditato da WordPress (cache locale per query veloci);
            // la fonte resta wp_accounts via pivot (Fase 6). Nullo = nessun livello (utente base).
            $table->string('membership_level')->nullable()->after('role');
            $table->timestamp('membership_synced_at')->nullable()->after('membership_level');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'membership_level', 'membership_synced_at']);
        });
    }
};
