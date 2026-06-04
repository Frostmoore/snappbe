<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Path dell'immagine caricata sul disco "public" (alternativa all'URL esterno).
        Schema::table('posts', function (Blueprint $table) {
            $table->string('cover_path')->nullable()->after('cover_url');
        });

        Schema::table('push_notifications', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('image_url');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('cover_path');
        });

        Schema::table('push_notifications', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
