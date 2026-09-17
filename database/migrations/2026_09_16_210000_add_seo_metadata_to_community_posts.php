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
        Schema::table('community_posts', function (Blueprint $table): void {
            $table->string('meta_title', 70)->nullable()->after('external_url');
            $table->string('meta_description', 180)->nullable()->after('meta_title');
            $table->text('meta_keywords')->nullable()->after('meta_description');
            $table->string('meta_robots', 100)->nullable()->after('meta_keywords');
            $table->string('canonical_url', 500)->nullable()->after('meta_robots');
            $table->string('og_title')->nullable()->after('canonical_url');
            $table->text('og_description')->nullable()->after('og_title');
            $table->string('og_type', 30)->default('article')->after('og_description');
            $table->string('twitter_title')->nullable()->after('og_type');
            $table->text('twitter_description')->nullable()->after('twitter_title');
            $table->string('twitter_card', 30)->default('summary_large_image')->after('twitter_description');
            $table->boolean('seo_is_custom')->default(false)->after('twitter_card');
        });

        $baseUrl = rtrim((string) config('app.url'), '/');

        DB::table('community_posts')
            ->select(['id', 'slug', 'title', 'body_html', 'body_markdown', 'external_url'])
            ->orderBy('id')
            ->chunkById(200, function ($posts) use ($baseUrl): void {
                foreach ($posts as $post) {
                    $title = Str::squish(strip_tags((string) $post->title));
                    $suffix = ' — ЛогистРу';
                    $titleLimit = 70 - mb_strlen($suffix);
                    $metaTitle = mb_stripos($title, 'логистру') !== false
                        ? Str::limit($title, 70, '')
                        : Str::limit($title, max(1, $titleLimit), '').$suffix;

                    $body = Str::squish(html_entity_decode(strip_tags(
                        (string) ($post->body_html ?: $post->body_markdown)
                    ), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    $description = $body !== ''
                        ? Str::limit($body, 180, '')
                        : Str::limit($title.'. Обсуждение в сообществе логистов и перевозчиков.', 180, '');

                    DB::table('community_posts')->where('id', $post->id)->update([
                        'meta_title' => $metaTitle,
                        'meta_description' => $description,
                        'meta_keywords' => 'логистика, грузоперевозки, сообщество логистов',
                        'meta_robots' => 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
                        'canonical_url' => $baseUrl.'/community/p/'.$post->id.'/'.$post->slug,
                        'og_title' => Str::limit($title, 255, ''),
                        'og_description' => $description,
                        'twitter_title' => Str::limit($title, 255, ''),
                        'twitter_description' => $description,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('community_posts', function (Blueprint $table): void {
            $table->dropColumn([
                'meta_title',
                'meta_description',
                'meta_keywords',
                'meta_robots',
                'canonical_url',
                'og_title',
                'og_description',
                'og_type',
                'twitter_title',
                'twitter_description',
                'twitter_card',
                'seo_is_custom',
            ]);
        });
    }
};
