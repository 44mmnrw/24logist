<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_users', function (Blueprint $table): void {
            $table->id();
            $table->string('username', 30)->unique();
            $table->string('role', 20)->default('user')->index();
            $table->integer('karma')->default(0);
            $table->timestamp('onboarded_at')->nullable();
            $table->timestamp('terms_accepted_at')->nullable();
            $table->timestamp('suspended_until')->nullable()->index();
            $table->timestamp('banned_at')->nullable()->index();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('community_identities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('community_user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 20);
            $table->string('provider_user_id', 100);
            $table->boolean('bot_access')->default(false);
            $table->boolean('notifications_enabled')->default(false);
            $table->string('bot_status', 20)->default('unknown');
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'provider_user_id'], 'ci_provider_user_unique');
            $table->unique(['community_user_id', 'provider'], 'ci_user_provider_unique');
        });

        Schema::create('community_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('description', 500)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('posting_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('community_posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('community_user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('community_category_id')->constrained()->restrictOnDelete();
            $table->string('slug', 200)->index();
            $table->string('title', 180);
            $table->text('body_markdown')->nullable();
            $table->longText('body_html')->nullable();
            $table->string('external_url', 2048)->nullable();
            $table->string('status', 20)->default('published')->index();
            $table->integer('score')->default(1)->index();
            $table->unsignedInteger('comments_count')->default(0);
            $table->double('hot_score')->default(0)->index();
            $table->boolean('is_pinned')->default(false)->index();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['community_category_id', 'status', 'published_at'], 'cp_category_status_published_idx');
        });

        Schema::create('community_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('community_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('community_user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('community_comments')->nullOnDelete();
            $table->unsignedBigInteger('root_id')->nullable()->index();
            $table->unsignedTinyInteger('depth')->default(0);
            $table->text('body_markdown')->nullable();
            $table->longText('body_html')->nullable();
            $table->string('status', 20)->default('published')->index();
            $table->integer('score')->default(1)->index();
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['community_post_id', 'root_id', 'created_at'], 'cc_post_root_created_idx');
        });

        Schema::create('community_post_votes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('community_user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('community_post_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('value');
            $table->timestamps();
            $table->unique(['community_user_id', 'community_post_id'], 'cpv_user_post_unique');
        });

        Schema::create('community_comment_votes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('community_user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('community_comment_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('value');
            $table->timestamps();
            $table->unique(['community_user_id', 'community_comment_id'], 'ccv_user_comment_unique');
        });

        Schema::create('community_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('community_user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('target_type', 20);
            $table->unsignedBigInteger('target_id');
            $table->string('reason', 30);
            $table->text('details')->nullable();
            $table->string('status', 20)->default('open')->index();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['target_type', 'target_id', 'status'], 'cr_target_status_idx');
        });

        Schema::create('community_moderation_actions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('community_user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('target_type', 20);
            $table->unsignedBigInteger('target_id');
            $table->string('action', 30);
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['target_type', 'target_id'], 'cma_target_idx');
        });

        Schema::create('community_login_challenges', function (Blueprint $table): void {
            $table->id();
            $table->string('token_hash', 64)->unique();
            $table->string('browser_session_hash', 64);
            $table->foreignId('link_to_user_id')->nullable()->constrained('community_users')->nullOnDelete();
            $table->foreignId('community_user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('expires_at')->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('community_notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('community_user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('community_users')->nullOnDelete();
            $table->string('type', 40);
            $table->string('target_type', 20);
            $table->unsignedBigInteger('target_id');
            $table->json('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->unique(['community_user_id', 'type', 'target_type', 'target_id'], 'cn_user_type_target_unique');
        });

        Schema::create('community_notification_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->uuid('community_notification_id');
            $table->foreign('community_notification_id', 'cnd_notification_fk')->references('id')->on('community_notifications')->cascadeOnDelete();
            $table->foreignId('community_identity_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 20);
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['community_notification_id', 'community_identity_id'], 'cnd_notification_identity_unique');
        });

        $now = now();
        DB::table('community_categories')->insert([
            ['name' => 'Общие вопросы', 'slug' => 'general', 'description' => 'Общение о логистике и работе сообщества.', 'sort_order' => 10, 'is_active' => true, 'posting_enabled' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Перевозчики', 'slug' => 'carriers', 'description' => 'Практика перевозок, транспорт и водители.', 'sort_order' => 20, 'is_active' => true, 'posting_enabled' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Грузовладельцы', 'slug' => 'cargo-owners', 'description' => 'Поиск решений и обмен опытом грузовладельцев.', 'sort_order' => 30, 'is_active' => true, 'posting_enabled' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'ЭДО и законодательство', 'slug' => 'edo-law', 'description' => 'Электронные документы и изменения законодательства.', 'sort_order' => 40, 'is_active' => true, 'posting_enabled' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Работа с 24Logist', 'slug' => '24logist', 'description' => 'Вопросы о платформе и предложения по развитию.', 'sort_order' => 50, 'is_active' => true, 'posting_enabled' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        foreach ([
            'community_notification_deliveries', 'community_notifications', 'community_login_challenges',
            'community_moderation_actions', 'community_reports', 'community_comment_votes',
            'community_post_votes', 'community_comments', 'community_posts', 'community_categories',
            'community_identities', 'community_users',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
