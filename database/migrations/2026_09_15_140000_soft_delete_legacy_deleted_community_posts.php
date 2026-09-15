<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('community_posts')
            ->where('status', 'deleted')
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now()]);
    }

    public function down(): void
    {
        DB::table('community_posts')
            ->where('status', 'deleted')
            ->update(['deleted_at' => null]);
    }
};
