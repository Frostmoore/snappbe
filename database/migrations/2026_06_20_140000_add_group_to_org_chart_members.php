<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('org_chart_members', function (Blueprint $table) {
            // Sezione/area (es. "Direzione", "Ufficio Legale"): l'organigramma è
            // raggruppato per sezione come sul sito, non più ad albero.
            $table->string('group')->nullable()->after('name');
            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::table('org_chart_members', function (Blueprint $table) {
            $table->dropIndex(['group']);
            $table->dropColumn('group');
        });
    }
};
