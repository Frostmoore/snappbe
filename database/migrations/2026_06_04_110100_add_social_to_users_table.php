<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Identità social (Google/Apple). provider_id = id univoco presso il provider.
            $table->string('provider')->nullable()->after('membership_synced_at');
            $table->string('provider_id')->nullable()->after('provider');
            $table->index(['provider', 'provider_id']);

            // Un utente registrato solo via social può non avere password.
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['provider', 'provider_id']);
            $table->dropColumn(['provider', 'provider_id']);
        });
    }
};
