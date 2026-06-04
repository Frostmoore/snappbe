<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_links', function (Blueprint $table) {
            $table->id();
            // Un utente app è collegato ad UN account WP (one-to-one in pratica).
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('wp_account_id')->constrained('wp_accounts')->cascadeOnDelete();
            $table->string('status')->default('verified'); // pending|verified
            $table->string('verification_method')->nullable();
            $table->timestamp('linked_at')->nullable();
            $table->timestamps();

            $table->index('wp_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_links');
    }
};
