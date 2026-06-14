<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ruolo WP ESATTO (slug + nome) ereditato dal sito. Distinto dal ruolo app
 * (`users.role` = permessi member/staff/admin/superadmin): qui conserviamo la
 * designazione SNA verbatim (es. presidente / Presidente) per differenziare i
 * contenuti dell'area riservata.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('wp_role')->nullable()->after('membership_level');
            $table->string('wp_role_label')->nullable()->after('wp_role');
        });

        Schema::table('wp_accounts', function (Blueprint $table) {
            $table->string('role')->nullable()->after('roles');
            $table->string('role_label')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['wp_role', 'wp_role_label']);
        });

        Schema::table('wp_accounts', function (Blueprint $table) {
            $table->dropColumn(['role', 'role_label']);
        });
    }
};
