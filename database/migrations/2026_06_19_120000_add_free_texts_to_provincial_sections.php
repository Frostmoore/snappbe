<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provincial_sections', function (Blueprint $table) {
            // Due testi liberi mostrati sotto il titolo nell'app:
            // text_bold = grigio grassetto, text_italic = grigio corsivo.
            $table->text('text_bold')->nullable()->after('name');
            $table->text('text_italic')->nullable()->after('text_bold');
        });
    }

    public function down(): void
    {
        Schema::table('provincial_sections', function (Blueprint $table) {
            $table->dropColumn(['text_bold', 'text_italic']);
        });
    }
};
