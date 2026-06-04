<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wp_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wp_user_id')->unique();
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->string('level')->nullable();       // key livello normalizzato (da plugin)
            $table->string('level_label')->nullable();
            $table->json('roles')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wp_accounts');
    }
};
