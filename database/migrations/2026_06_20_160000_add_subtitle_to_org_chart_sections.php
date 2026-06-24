<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('org_chart_sections', function (Blueprint $table) {
            // Sottotitolo (eyebrow) mostrato sopra il titolo, con le bande oro.
            $table->string('subtitle')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('org_chart_sections', function (Blueprint $table) {
            $table->dropColumn('subtitle');
        });
    }
};
