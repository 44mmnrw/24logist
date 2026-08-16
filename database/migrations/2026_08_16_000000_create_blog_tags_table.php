<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->string('meta_robots')->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image_path')->nullable();
            $table->string('og_type')->default('website');
            $table->string('twitter_title')->nullable();
            $table->text('twitter_description')->nullable();
            $table->string('twitter_image_path')->nullable();
            $table->string('twitter_card')->default('summary_large_image');
            $table->string('schema_type')->default('CollectionPage');
            $table->string('schema_headline')->nullable();
            $table->text('schema_description')->nullable();
            $table->string('schema_image_path')->nullable();
            $table->timestamps();
        });

        $names = [];

        DB::table('blog_posts')->orderBy('id')->pluck('tags')->each(function ($value) use (&$names): void {
            $tags = is_array($value) ? $value : json_decode((string) $value, true);

            foreach (is_array($tags) ? $tags : [] as $tag) {
                $name = trim((string) $tag);

                if ($name !== '') {
                    $names[mb_strtolower($name)] = $name;
                }
            }
        });

        $usedSlugs = [];

        foreach ($names as $name) {
            $baseSlug = Str::slug($name) ?: 'tag-'.substr(md5($name), 0, 10);
            $slug = $baseSlug;
            $suffix = 2;

            while (isset($usedSlugs[$slug])) {
                $slug = $baseSlug.'-'.$suffix++;
            }

            $usedSlugs[$slug] = true;

            DB::table('blog_tags')->insert([
                'name' => $name,
                'slug' => $slug,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_tags');
    }
};
