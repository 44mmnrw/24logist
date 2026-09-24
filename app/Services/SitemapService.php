<?php

namespace App\Services;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\CmsPage;
use App\Models\CommunityCategory;
use App\Models\CommunityPost;
use App\Models\CommunitySeoPage;
use App\Models\CommunityUser;
use App\Models\LandingSection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

final class SitemapService
{
    private const CACHE_KEY = 'seo.sitemap.xml';

    /** @var list<string> */
    private const DISALLOWED_PATHS = [
        '/admin',
        '/admin/',
        '/lw-',
        '/livewire/',
        '/leads/',
        '/up',
        '/storage/',
        '/vendor/',
        '/bootstrap/',
        '/community/auth/',
        '/community/login',
        '/community/register',
        '/community/moderation/',
        '/community/notifications',
        '/community/onboarding',
        '/community/settings',
        '/community/submit',
    ];

    public function robots(): string
    {
        $siteUrl = rtrim((string) config('app.url'), '/');
        $sitemapUrl = $siteUrl.'/sitemap.xml';

        $lines = [
            '# robots.txt — '.($siteUrl !== '' ? $siteUrl : '24logist.ru'),
            '',
        ];

        foreach (['*', 'Yandex', 'Googlebot'] as $agent) {
            $lines = array_merge($lines, $this->robotsBlock($agent));

            if ($agent === 'Yandex') {
                $lines[] = 'Clean-param: utm_source&utm_medium&utm_campaign&utm_term&utm_content&yclid&gclid&fbclid&_openstat';
            }

            $lines[] = '';
        }

        foreach (['GPTBot', 'ChatGPT-User', 'Google-Extended', 'anthropic-ai', 'ClaudeBot', 'PerplexityBot', 'Applebot-Extended'] as $agent) {
            $lines = array_merge($lines, ['User-agent: '.$agent, 'Allow: /', 'Allow: /llms.txt', '']);
        }

        foreach (['TelegramBot', 'Twitterbot', 'facebookexternalhit', 'LinkedInBot', 'Slackbot'] as $agent) {
            $lines = array_merge($lines, ['User-agent: '.$agent, 'Allow: /', '']);
        }

        if ($siteUrl !== '') {
            $host = parse_url($siteUrl, PHP_URL_HOST);

            if (is_string($host) && $host !== '') {
                $lines[] = 'Host: '.$host;
            }
        }

        $lines[] = 'Sitemap: '.$sitemapUrl;

        return implode("\n", $lines)."\n";
    }

    /**
     * @return list<string>
     */
    private function robotsBlock(string $agent): array
    {
        $lines = ['User-agent: '.$agent, 'Allow: /'];

        foreach (self::DISALLOWED_PATHS as $path) {
            $lines[] = 'Disallow: '.$path;
        }

        // Open Graph / link-preview crawlers must fetch public OG assets under /storage/.
        $lines[] = 'Allow: /images/';
        $lines[] = 'Allow: /llms.txt';
        $lines[] = 'Allow: /storage/site/og/';
        $lines[] = 'Allow: /storage/site/favicon/';
        $lines[] = 'Allow: /storage/site/apple-touch-icon/';
        $lines[] = 'Allow: /storage/landing/';
        $lines[] = 'Allow: /storage/community/seo/';

        return $lines;
    }

    public function xml(): string
    {
        return Cache::remember(self::CACHE_KEY, now()->addHour(), fn (): string => $this->buildXml());
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return list<array{loc: string, lastmod: ?string, changefreq: string, priority: string}>
     */
    public function urls(): array
    {
        $urls = [];

        $homeLastmod = LandingSection::query()->max('updated_at');

        $urls[] = [
            'loc' => rtrim((string) config('app.url'), '/').'/',
            'lastmod' => $this->formatLastmod($homeLastmod),
            'changefreq' => 'weekly',
            'priority' => '1.0',
        ];

        $urls[] = [
            'loc' => route('epd-game'),
            'lastmod' => null,
            'changefreq' => 'monthly',
            'priority' => '0.6',
        ];

        $urls[] = [
            'loc' => route('etrn-roulette'),
            'lastmod' => null,
            'changefreq' => 'monthly',
            'priority' => '0.6',
        ];

        $pages = CmsPage::query()
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->get();

        foreach ($pages as $page) {
            $loc = $page->slug === 'privacy-policy'
                ? route('legal.privacy_policy')
                : route('pages.show', $page->slug);

            $urls[] = [
                'loc' => $loc,
                'lastmod' => $this->formatLastmod($page->updated_at),
                'changefreq' => 'monthly',
                'priority' => $page->slug === 'contacts' ? '0.9' : '0.7',
            ];
        }

        $blogLastmod = BlogPost::query()
            ->published()
            ->max('updated_at');

        $urls[] = [
            'loc' => route('blog.index'),
            'lastmod' => $this->formatLastmod($blogLastmod),
            'changefreq' => 'weekly',
            'priority' => '0.8',
        ];

        $posts = BlogPost::query()
            ->published()
            ->orderByDesc('published_at')
            ->get();

        foreach ($posts as $post) {
            $urls[] = [
                'loc' => $post->getUrl(),
                'lastmod' => $this->formatLastmod($post->updated_at),
                'changefreq' => 'monthly',
                'priority' => $post->is_featured ? '0.8' : '0.6',
            ];
        }

        $publishedCategoryIds = $posts
            ->pluck('blog_category_id')
            ->filter()
            ->unique()
            ->values();

        if ($publishedCategoryIds->isNotEmpty()) {
            $categories = BlogCategory::query()
                ->where('is_active', true)
                ->whereKey($publishedCategoryIds)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            foreach ($categories as $category) {
                $urls[] = [
                    'loc' => $category->getUrl(),
                    'lastmod' => $this->formatLastmod($category->updated_at),
                    'changefreq' => 'weekly',
                    'priority' => '0.6',
                ];
            }
        }

        $publishedTagNames = $posts
            ->flatMap(fn (BlogPost $post): array => (array) $post->tags)
            ->filter(fn ($tag): bool => filled($tag))
            ->unique()
            ->values();

        if ($publishedTagNames->isNotEmpty()) {
            $tags = BlogTag::query()
                ->whereIn('name', $publishedTagNames)
                ->orderBy('name')
                ->get();

            foreach ($tags as $tag) {
                $urls[] = [
                    'loc' => $tag->getUrl(),
                    'lastmod' => $this->formatLastmod($tag->updated_at),
                    'changefreq' => 'weekly',
                    'priority' => '0.5',
                ];
            }
        }

        if (app(SiteSettingsService::class)->communityEnabled()) {
            $seoPages = CommunitySeoPage::query()->get()->keyBy('page_key');
            $addCommunityUrl = function (string $key, string $loc, mixed $lastmod, string $frequency, string $priority, ?string $robots = null, ?string $canonical = null) use (&$urls, $seoPages): void {
                $page = $seoPages->get($key);
                $settings = $page?->settings ?? [];
                $robots = filled($settings['meta_robots'] ?? null) ? $settings['meta_robots'] : ($robots ?: 'index, follow');
                $canonical = filled($settings['canonical_url'] ?? null) ? $settings['canonical_url'] : ($canonical ?: $loc);
                if (($settings['include_in_sitemap'] ?? true) === false
                    || preg_match('/\b(noindex|none)\b/i', $robots)
                    || rtrim($canonical, '/') !== rtrim($loc, '/')) {
                    return;
                }
                $urls[] = [
                    'loc' => $loc,
                    'lastmod' => $this->formatLastmod(collect([$lastmod, $page?->updated_at])->filter()->map(fn ($date) => Carbon::parse($date))->max()),
                    'changefreq' => $frequency,
                    'priority' => $priority,
                ];
            };
            $communityLastmod = CommunityPost::query()->published()->max('updated_at');
            $addCommunityUrl('community.index', route('community.index'), $communityLastmod, 'daily', '0.8');
            foreach (['community.rules', 'community.privacy'] as $route) {
                $addCommunityUrl($route, route($route), null, 'monthly', '0.3');
            }

            CommunityCategory::query()->active()->orderBy('sort_order')->get()->each(function (CommunityCategory $category) use ($addCommunityUrl): void {
                $addCommunityUrl('category:'.$category->id, route('community.categories.show', $category), $category->updated_at, 'daily', '0.6');
            });

            CommunityPost::query()->published()->orderByDesc('published_at')->get()->each(function (CommunityPost $post) use ($addCommunityUrl): void {
                $addCommunityUrl('post:'.$post->id, $post->getUrl(), $post->updated_at, 'weekly', '0.6', $post->meta_robots, $post->canonical_url);
            });

            CommunityUser::query()->whereNotNull('onboarded_at')->whereNotNull('terms_accepted_at')->whereNotNull('username')->each(function (CommunityUser $user) use ($addCommunityUrl): void {
                $addCommunityUrl('profile:'.$user->id, route('community.profile', $user), $user->updated_at, 'weekly', '0.3', 'noindex, follow');
            });
        }

        return $urls;
    }

    private function buildXml(): string
    {
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($this->urls() as $url) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>'.$this->escape($url['loc']).'</loc>';

            if ($url['lastmod'] !== null) {
                $lines[] = '    <lastmod>'.$url['lastmod'].'</lastmod>';
            }

            $lines[] = '    <changefreq>'.$url['changefreq'].'</changefreq>';
            $lines[] = '    <priority>'.$url['priority'].'</priority>';
            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines)."\n";
    }

    private function formatLastmod(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return Carbon::parse($value)->toDateString();
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
