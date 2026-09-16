<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_ai_scenarios', function (Blueprint $table): void {
            $table->string('source_post_title', 180)->nullable()->after('manual_topic_body');
        });

        DB::table('community_ai_scenarios')
            ->where('mode', 'manual')
            ->whereNull('source_post_title')
            ->update(['source_post_title' => DB::raw('title')]);
    }

    public function down(): void
    {
        Schema::table('community_ai_scenarios', function (Blueprint $table): void {
            $table->dropColumn('source_post_title');
        });
    }
};
