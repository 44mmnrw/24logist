<?php

namespace App\Services;

use App\Models\CmsPage;
use App\Models\LandingSection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

final class SitemapService
{
    private const CACHE_KEY = 'seo.sitemap.xml';

    public function robots(): string
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /admin/',
            '',
            'Sitemap: '.url('/sitemap.xml'),
        ];

        return implode("\n", $lines)."\n";
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
            'loc' => url('/'),
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
