<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Area riservata costruita dal pannello: Tile → Sezioni → Elementi scaricabili.
 * Ogni livello ha `visible_roles` (slug ruoli WP): vuoto = visibile a tutti.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reserved_tiles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('icon_path')->nullable(); // immagine/icona caricata
            $table->string('color')->nullable();      // hex sfondo
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('visible_roles')->nullable(); // slug ruoli WP; vuoto = tutti
            $table->timestamps();
        });

        Schema::create('reserved_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reserved_tile_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('visible_roles')->nullable();
            $table->timestamps();
        });

        Schema::create('reserved_elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reserved_section_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path')->nullable();   // file caricato da scaricare
            $table->string('external_url')->nullable(); // in alternativa, link esterno
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('visible_roles')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reserved_elements');
        Schema::dropIfExists('reserved_sections');
        Schema::dropIfExists('reserved_tiles');
    }
};
