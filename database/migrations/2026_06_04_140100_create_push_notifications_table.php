<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('image_url')->nullable();
            $table->string('deep_link')->nullable();
            $table->json('data')->nullable();              // dati arbitrari extra

            $table->string('target')->default('all');      // all|level|users
            $table->string('target_level')->nullable();    // se target=level
            $table->json('target_user_ids')->nullable();   // se target=users

            $table->string('status')->default('draft');    // draft|sent
            $table->timestamp('sent_at')->nullable();
            $table->json('stats')->nullable();             // esito invio
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_notifications');
    }
};
