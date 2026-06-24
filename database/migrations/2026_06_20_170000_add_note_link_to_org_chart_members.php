<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('org_chart_members', function (Blueprint $table) {
            $table->text('note')->nullable()->after('role');   // testo libero sotto il ruolo
            $table->string('link')->nullable()->after('note');  // se valorizzato, il tap apre il link
        });
    }

    public function down(): void
    {
        Schema::table('org_chart_members', function (Blueprint $table) {
            $table->dropColumn(['note', 'link']);
        });
    }
};
