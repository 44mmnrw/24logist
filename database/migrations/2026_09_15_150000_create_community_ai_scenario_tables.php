<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_ai_sources', function (Blueprint $table): void {
            $table->id();
            $table->string('platform', 20)->default('max');
            $table->string('name', 120);
            $table->string('external_chat_id', 100);
            $table->string('public_url', 2048)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->unique(['platform', 'external_chat_id'], 'cais_platform_chat_unique');
        });

        Schema::create('community_ai_source_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('community_ai_source_id')->constrained('community_ai_sources')->cascadeOnDelete();
            $table->string('external_message_id', 191);
            $table->char('sender_key', 64)->nullable();
            $table->text('sender_name')->nullable();
            $table->longText('text');
            $table->char('content_hash', 64)->index();
            $table->timestamp('sent_at')->index();
            $table->longText('raw_payload')->nullable();
            $table->timestamps();
            $table->unique(['community_ai_source_id', 'external_message_id'], 'caism_source_message_unique');
            $table->index(['community_ai_source_id', 'sent_at'], 'caism_source_sent_idx');
        });

        Schema::create('community_ai_scenarios', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('community_category_id')->nullable()->constrained('community_categories')->nullOnDelete();
            $table->json('source_ids');
            $table->timestamp('source_from');
            $table->timestamp('source_to');
            $table->string('title', 180)->nullable();
            $table->longText('editor_brief')->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->timestamp('planned_at')->nullable()->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('community_ai_scenario_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('community_ai_scenario_id')->constrained('community_ai_scenarios')->cascadeOnDelete();
            $table->foreignId('community_ai_persona_id')->constrained('community_ai_personas')->restrictOnDelete();
            $table->foreignId('parent_step_id')->nullable()->constrained('community_ai_scenario_steps')->nullOnDelete();
            $table->string('type', 20);
            $table->unsignedSmallInteger('sequence');
            $table->unsignedSmallInteger('planned_delay_minutes')->default(0);
            $table->string('purpose', 255)->nullable();
            $table->string('draft_title', 180)->nullable();
            $table->longText('draft_body')->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->foreignId('community_post_id')->nullable()->constrained('community_posts')->nullOnDelete();
            $table->foreignId('community_comment_id')->nullable()->constrained('community_comments')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('published_at')->nullable();
            $table->text('last_error')->nullable();
            $table->string('idempotency_key', 100)->unique();
            $table->timestamps();
            $table->unique(['community_ai_scenario_id', 'sequence'], 'caiss_scenario_sequence_unique');
        });

        Schema::create('community_ai_generations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('community_ai_scenario_id')->constrained('community_ai_scenarios')->cascadeOnDelete();
            $table->foreignId('community_ai_scenario_step_id')->nullable()->constrained('community_ai_scenario_steps')->cascadeOnDelete();
            $table->foreignId('community_ai_persona_id')->constrained('community_ai_personas')->restrictOnDelete();
            $table->string('purpose', 30);
            $table->string('status', 20)->default('pending')->index();
            $table->longText('request_messages')->nullable();
            $table->longText('response_payload')->nullable();
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_ai_generations');
        Schema::dropIfExists('community_ai_scenario_steps');
        Schema::dropIfExists('community_ai_scenarios');
        Schema::dropIfExists('community_ai_source_messages');
        Schema::dropIfExists('community_ai_sources');
    }
};
