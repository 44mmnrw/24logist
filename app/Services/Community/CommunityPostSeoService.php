<?php

namespace App\Services\Community;

use App\Models\CommunityPost;
use App\Support\OpenGraph;
use Illuminate\Support\Str;

final class CommunityPostSeoService
{
    /** @return array<string, string> */
    public function metadata(CommunityPost $post): array
    {
        $title = Str::squish(strip_tags((string) $post->title));
        $plainBody = Str::squish(html_entity_decode(strip_tags(
            (string) ($post->body_html ?: $post->body_markdown)
        ), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        $description = $plainBody !== ''
            ? Str::limit($plainBody, 180, '')
            : Str::limit($title.'. Обсуждение в сообществе логистов и перевозчиков.', 180, '');

        $category = $post->relationLoaded('category')
            ? $post->category?->name
            : $post->category()->value('name');
        $keywords = array_values(array_unique(array_filter([
            filled($category) ? trim((string) $category) : null,
            'логистика',
            'грузоперевозки',
            'сообщество логистов',
        ])));

        return [
            'meta_title' => $this->metaTitle($title),
            'meta_description' => $description,
            'meta_keywords' => implode(', ', $keywords),
            'meta_robots' => OpenGraph::ROBOTS_INDEX,
            'og_title' => Str::limit($title, 255, ''),
            'og_description' => $description,
            'og_type' => 'article',
            'twitter_title' => Str::limit($title, 255, ''),
            'twitter_description' => $description,
            'twitter_card' => 'summary_large_image',
        ];
    }

    public function fill(CommunityPost $post, bool $overwrite = false): void
    {
        foreach ($this->metadata($post) as $field => $value) {
            if ($overwrite || blank($post->getAttribute($field))) {
                $post->setAttribute($field, $value);
            }
        }
    }

    private function metaTitle(string $title): string
    {
        $suffix = ' — '.OpenGraph::SITE_NAME;

        if (mb_stripos($title, OpenGraph::SITE_NAME) !== false) {
            return Str::limit($title, 70, '');
        }

        return Str::limit($title, max(1, 70 - mb_strlen($suffix)), '').$suffix;
    }
}
