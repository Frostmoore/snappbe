<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            // user_id nullable: anche i device anonimi possono ricevere notifiche pubbliche.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('fcm_token')->unique();
            $table->string('platform')->nullable(); // android|ios|web
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
