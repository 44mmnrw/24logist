<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_keyword_clusters', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('target_url', 500)->nullable();
            $table->string('search_intent')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('seo_research_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('type');
            $table->string('source')->default('yandex');
            $table->string('status')->default('pending');
            $table->string('region_id', 100)->default('225');
            $table->string('device', 32)->default('DEVICE_ALL');
            $table->unsignedInteger('total_items')->default(0);
            $table->unsignedInteger('processed_items')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->json('metadata')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('started_at');
        });

        Schema::create('seo_keywords', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seo_keyword_cluster_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phrase', 500);
            $table->string('normalized_phrase', 500);
            $table->char('identity_hash', 64)->unique();
            $table->string('region_id', 100)->default('225');
            $table->string('device', 32)->default('DEVICE_ALL');
            $table->string('target_url', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('latest_wordstat_count')->nullable();
            $table->timestamp('wordstat_updated_at')->nullable();
            $table->unsignedSmallInteger('latest_position')->nullable();
            $table->string('latest_result_url', 2048)->nullable();
            $table->timestamp('position_checked_at')->nullable();
            $table->timestamps();

            $table->index(['seo_keyword_cluster_id', 'is_active']);
            $table->index('latest_position');
            $table->index('position_checked_at');
        });

        Schema::create('seo_keyword_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seo_keyword_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seo_research_run_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source')->default('yandex');
            $table->unsignedBigInteger('wordstat_count')->nullable();
            $table->unsignedSmallInteger('position')->nullable();
            $table->string('result_url', 2048)->nullable();
            $table->timestamp('recorded_at');
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->unique(['seo_keyword_id', 'seo_research_run_id']);
            $table->index(['seo_keyword_id', 'recorded_at']);
            $table->index(['source', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_keyword_snapshots');
        Schema::dropIfExists('seo_keywords');
        Schema::dropIfExists('seo_research_runs');
        Schema::dropIfExists('seo_keyword_clusters');
    }
};
