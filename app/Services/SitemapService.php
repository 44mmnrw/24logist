<?php

namespace App\Services;

use App\Models\CmsPage;
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
            $lines[] = 'Host: '.$siteUrl;
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
