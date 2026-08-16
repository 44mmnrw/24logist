<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\CmsPage;
use Illuminate\Support\Str;

final class LlmsTxtService
{
    public function __construct(
        private readonly SiteSettingsService $settings,
    ) {}

    public function generate(): string
    {
        $content = str_replace(
            ["\r\n", "\r"],
            "\n",
            (string) ($this->settings->get()->llms_txt_extra ?? ''),
        );

        return $content === '' ? '' : rtrim($content, "\n")."\n";
    }

    /**
     * @return array{content: string, pages: int, posts: int, tags: int}
     */
    public function refreshFromPublishedContent(): array
    {
        $settings = $this->settings->get();
        $pages = CmsPage::query()
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();
        $posts = BlogPost::query()
            ->published()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();
        $usedTagNames = $posts
            ->flatMap(fn (BlogPost $post): array => (array) $post->tags)
            ->map(fn ($tag): string => trim((string) $tag))
            ->filter()
            ->unique()
            ->values();
        $tags = $usedTagNames->isEmpty()
            ? collect()
            : BlogTag::query()->whereIn('name', $usedTagNames)->orderBy('name')->get();

        $brand = trim((string) $settings->org_brand_name);

        if ($brand === '' || mb_strtolower($brand) === 'laravel') {
            $brand = 'ЛогистРу';
        }
        $summary = $this->normalizeSiteSummary(
            $this->plainText($settings->ai_site_summary, 500),
        );
        $lines = ['# '.$this->markdownLabel($brand)];

        if ($summary !== '') {
            $lines[] = '';
            $lines[] = '> '.$summary;
        }

        $lines = array_merge($lines, [
            '',
            '## Основные разделы',
            '',
            $this->link('Главная', url('/'), $summary),
            $this->link('Блог', route('blog.index'), $this->plainText($settings->blog_description)),
        ]);

        if ($pages->isNotEmpty()) {
            $lines = array_merge($lines, ['', '## Страницы', '']);

            foreach ($pages as $page) {
                $url = $page->slug === 'privacy-policy'
                    ? route('legal.privacy_policy')
                    : route('pages.show', $page->slug);
                $description = $this->plainText($page->meta_description)
                    ?: $this->plainText($page->renderBody());

                $lines[] = $this->link($page->title, $url, $description);
            }
        }

        if ($posts->isNotEmpty()) {
            $lines = array_merge($lines, ['', '## Статьи блога', '']);

            foreach ($posts as $post) {
                $description = $this->plainText($post->meta_description)
                    ?: $this->plainText($post->displayExcerpt());

                $lines[] = $this->link($post->title, $post->getUrl(), $description);
            }
        }

        if ($tags->isNotEmpty()) {
            $lines = array_merge($lines, ['', '## Тематические страницы', '']);

            foreach ($tags as $tag) {
                $description = $this->plainText($tag->meta_description)
                    ?: $this->plainText($tag->description);

                $lines[] = $this->link($tag->displayH1(), $tag->getUrl(), $description);
            }
        }

        $content = implode("\n", $lines)."\n";

        $settings->forceFill(['llms_txt_extra' => $content])->save();
        $this->settings->clearCache();

        return [
            'content' => $content,
            'pages' => $pages->count(),
            'posts' => $posts->count(),
            'tags' => $tags->count(),
        ];
    }

    private function link(string $label, string $url, string $description = ''): string
    {
        $line = '- ['.$this->markdownLabel($label).']('.$url.')';

        return $description === '' ? $line : $line.': '.$description;
    }

    private function markdownLabel(mixed $value): string
    {
        return str_replace(['[', ']'], ['\\[', '\\]'], Str::squish(strip_tags((string) $value)));
    }

    private function plainText(mixed $value, int $limit = 240): string
    {
        $text = Str::squish(html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $text === '' ? '' : Str::limit($text, $limit);
    }

    private function normalizeSiteSummary(string $summary): string
    {
        return (string) preg_replace(
            '/тарифы от 1\s*600\s*₽\/мес\.?/iu',
            'тарифы от 2 900 ₽/мес.',
            $summary,
        );
    }
}
