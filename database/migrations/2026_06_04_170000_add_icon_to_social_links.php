<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_links', function (Blueprint $table) {
            $table->string('icon_path')->nullable()->after('label');   // png o svg caricato
            $table->string('background_color')->nullable()->after('icon_path');
            $table->string('icon_color')->nullable()->after('background_color'); // tinta per SVG
        });
    }

    public function down(): void
    {
        Schema::table('social_links', function (Blueprint $table) {
            $table->dropColumn(['icon_path', 'background_color', 'icon_color']);
        });
    }
};
