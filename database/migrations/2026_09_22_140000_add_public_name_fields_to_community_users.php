<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_users', function (Blueprint $table): void {
            $table->string('first_name', 50)->nullable()->after('display_name');
            $table->string('last_name', 50)->nullable()->after('first_name');
            $table->boolean('show_first_name')->default(false)->after('last_name');
            $table->boolean('show_last_name')->default(false)->after('show_first_name');
        });
    }

    public function down(): void
    {
        Schema::table('community_users', function (Blueprint $table): void {
            $table->dropColumn(['first_name', 'last_name', 'show_first_name', 'show_last_name']);
        });
    }
};
