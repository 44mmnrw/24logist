<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_categories', function (Blueprint $table): void {
            $table->string('seo_h1')->nullable()->after('description');
            $table->string('meta_title')->nullable()->after('seo_h1');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->text('meta_keywords')->nullable()->after('meta_description');
            $table->string('meta_robots')->nullable()->after('meta_keywords');
            $table->string('canonical_url', 500)->nullable()->after('meta_robots');
            $table->string('og_title')->nullable()->after('canonical_url');
            $table->text('og_description')->nullable()->after('og_title');
            $table->string('og_image_path')->nullable()->after('og_description');
            $table->string('og_type')->default('website')->after('og_image_path');
            $table->string('twitter_title')->nullable()->after('og_type');
            $table->text('twitter_description')->nullable()->after('twitter_title');
            $table->string('twitter_image_path')->nullable()->after('twitter_description');
            $table->string('twitter_card')->default('summary_large_image')->after('twitter_image_path');
            $table->string('schema_type')->default('CollectionPage')->after('twitter_card');
            $table->string('schema_headline')->nullable()->after('schema_type');
            $table->text('schema_description')->nullable()->after('schema_headline');
            $table->string('schema_image_path')->nullable()->after('schema_description');
        });
    }

    public function down(): void
    {
        Schema::table('blog_categories', function (Blueprint $table): void {
            $table->dropColumn([
                'seo_h1',
                'meta_title',
                'meta_description',
                'meta_keywords',
                'meta_robots',
                'canonical_url',
                'og_title',
                'og_description',
                'og_image_path',
                'og_type',
                'twitter_title',
                'twitter_description',
                'twitter_image_path',
                'twitter_card',
                'schema_type',
                'schema_headline',
                'schema_description',
                'schema_image_path',
            ]);
        });
    }
};
