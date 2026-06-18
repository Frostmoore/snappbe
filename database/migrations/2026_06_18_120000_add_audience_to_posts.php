<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Destinatario del post: governa SIA la visibilità in-app SIA la push.
            // all | level (usa min_level) | role (audience_role) | users (audience_user_ids)
            $table->string('audience')->default('all')->after('min_level');
            $table->string('audience_role')->nullable()->after('audience');
            $table->json('audience_user_ids')->nullable()->after('audience_role');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['audience', 'audience_role', 'audience_user_ids']);
        });
    }
};
