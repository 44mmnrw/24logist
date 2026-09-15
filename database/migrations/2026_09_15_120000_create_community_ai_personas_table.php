<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_ai_personas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('community_user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('slug', 80)->unique();
            $table->string('role_description', 255);
            $table->text('personality_description');
            $table->string('provider', 30)->default('timeweb');
            $table->uuid('provider_agent_id')->unique();
            $table->string('provider_base_url', 2048);
            $table->string('model', 100)->default('GPT-5.4 Mini');
            $table->unsignedSmallInteger('prompt_version')->default(1);
            $table->longText('system_prompt');
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('can_create_posts')->default(true);
            $table->boolean('can_create_comments')->default(true);
            $table->boolean('requires_review')->default(true)->index();
            $table->unsignedSmallInteger('daily_post_limit')->default(1);
            $table->unsignedSmallInteger('daily_comment_limit')->default(1);
            $table->unsignedSmallInteger('max_post_tokens')->default(1000);
            $table->unsignedSmallInteger('max_comment_tokens')->default(500);
            $table->json('settings')->nullable();
            $table->timestamp('last_acted_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_ai_personas');
    }
};
