<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_ai_scenarios', function (Blueprint $table): void {
            $table->string('mode', 20)->default('source')->after('id')->index();
            $table->foreignId('topic_persona_id')
                ->nullable()
                ->after('community_category_id')
                ->constrained('community_ai_personas')
                ->nullOnDelete();
            $table->longText('manual_topic_body')->nullable()->after('editor_brief');
        });
    }

    public function down(): void
    {
        Schema::table('community_ai_scenarios', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('topic_persona_id');
            $table->dropIndex(['mode']);
            $table->dropColumn(['mode', 'manual_topic_body']);
        });
    }
};
