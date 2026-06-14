<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Card di navigazione della home dell'app, gestibili dal pannello.
        Schema::create('home_sections', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('route');                       // destinazione (es. /newsletters)
            $table->string('layout')->default('half');     // wide | half
            $table->string('icon_path')->nullable();       // png o svg caricato
            $table->string('background_color')->nullable();
            $table->string('icon_color')->nullable();       // tinta per SVG
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_sections');
    }
};
