<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table): void {
            $table->string('author_type')->default('Person')->after('author_name');
            $table->string('author_url', 500)->nullable()->after('author_type');
            $table->string('twitter_title')->nullable()->after('og_type');
            $table->text('twitter_description')->nullable()->after('twitter_title');
            $table->string('twitter_image_path')->nullable()->after('twitter_description');
            $table->string('twitter_card')->default('summary_large_image')->after('twitter_image_path');
            $table->string('schema_type')->default('Article')->after('twitter_card');
            $table->string('schema_headline')->nullable()->after('schema_type');
            $table->text('schema_description')->nullable()->after('schema_headline');
            $table->string('schema_image_path')->nullable()->after('schema_description');
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table): void {
            $table->dropColumn([
                'author_type',
                'author_url',
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
