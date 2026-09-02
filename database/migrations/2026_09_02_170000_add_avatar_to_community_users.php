<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_users', function (Blueprint $table): void {
            $table->string('avatar_path', 500)->nullable()->after('username');
            $table->string('avatar_source', 20)->nullable()->after('avatar_path');
        });
    }

    public function down(): void
    {
        Schema::table('community_users', function (Blueprint $table): void {
            $table->dropColumn(['avatar_path', 'avatar_source']);
        });
    }
};
