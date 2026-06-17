<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Quando abbiamo già proposto il collegamento SNA per match email
            // (accettato o rifiutato): la proposta si fa UNA sola volta.
            $table->timestamp('sna_link_prompted_at')->nullable()->after('membership_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('sna_link_prompted_at');
        });
    }
};
