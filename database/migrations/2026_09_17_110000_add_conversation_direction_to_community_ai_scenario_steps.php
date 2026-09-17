<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_ai_scenario_steps', function (Blueprint $table): void {
            $table->string('conversation_move', 40)->nullable()->after('purpose');
            $table->unsignedSmallInteger('target_word_count')->nullable()->after('conversation_move');
        });
    }

    public function down(): void
    {
        Schema::table('community_ai_scenario_steps', function (Blueprint $table): void {
            $table->dropColumn(['conversation_move', 'target_word_count']);
        });
    }
};
