<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sna_password_resets', function (Blueprint $table) {
            // Reset password del SITO SNA via codice email (uno per email).
            $table->string('email')->primary();
            $table->string('token');       // codice hashato
            $table->unsignedBigInteger('wp_user_id');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sna_password_resets');
    }
};
