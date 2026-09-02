<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_users', function (Blueprint $table): void {
            $table->string('display_name', 50)->nullable()->after('username');
            $table->string('transport_role', 30)->nullable()->after('avatar_source');
            $table->text('bio')->nullable()->after('transport_role');
        });

        DB::table('community_users')->whereNull('display_name')->update([
            'display_name' => DB::raw('username'),
        ]);
    }

    public function down(): void
    {
        Schema::table('community_users', function (Blueprint $table): void {
            $table->dropColumn(['display_name', 'transport_role', 'bio']);
        });
    }
};
