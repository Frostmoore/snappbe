<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provincial_sections', function (Blueprint $table) {
            // Contenuto unico rich-text (HTML) mostrato nella card espandibile.
            $table->longText('body')->nullable()->after('name');
            // I due testi liberi separati non servono più.
            $table->dropColumn(['text_bold', 'text_italic']);
        });
    }

    public function down(): void
    {
        Schema::table('provincial_sections', function (Blueprint $table) {
            $table->text('text_bold')->nullable()->after('name');
            $table->text('text_italic')->nullable()->after('text_bold');
            $table->dropColumn('body');
        });
    }
};
