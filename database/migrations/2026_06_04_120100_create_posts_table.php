<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->default('generic');      // news|newsletter|tool|generic
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->string('cover_url')->nullable();
            $table->string('status')->default('draft');       // draft|published|archived
            $table->timestamp('published_at')->nullable();
            // min_level = key di access_levels richiesta per vedere il post. NULL = pubblico.
            $table->string('min_level')->nullable();
            $table->string('external_url')->nullable();        // es. link a form evento WP
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index('min_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
