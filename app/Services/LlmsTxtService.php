<?php

namespace App\Services;

use App\Support\OpenGraph;

final class LlmsTxtService
{
    public function __construct(
        private readonly SiteSettingsService $settings,
        private readonly SitemapService $sitemap,
    ) {}

    public function generate(): string
    {
        $site = $this->settings->get();
        $siteUrl = rtrim((string) config('app.url'), '/');
        $brand = filled($site->org_brand_name) ? (string) $site->org_brand_name : OpenGraph::SITE_NAME;
        $legal = trim((string) ($site->org_legal_name ?? ''));

        $lines = [
            '# '.$brand,
            '',
        ];

        $summary = trim((string) ($site->ai_site_summary ?? ''));

        if ($summary === '') {
            $summary = trim((string) ($site->og_description ?? ''));

            if ($summary === '') {
                $summary = 'Платформа «'.$brand.'» для управления логистикой, перевозками, контрагентами и водителями.';
            }
        }

        $lines[] = '> '.$summary;
        $lines[] = '';

        if ($legal !== '') {
            $lines[] = 'Оператор: '.$legal;
            $lines[] = '';
        }

        $lines[] = '## Основные страницы';
        $lines[] = '';

        foreach ($this->sitemap->urls() as $url) {
            $path = parse_url($url['loc'], PHP_URL_PATH) ?? '/';
            $label = match (true) {
                in_array($path, ['/', ''], true) => 'Главная',
                str_contains($path, 'contacts') => 'Контакты',
                str_contains($path, 'privacy-policy') => 'Политика обработки персональных данных',
                default => $this->humanizePath($path),
            };

            $lines[] = '- ['.$label.']('.$url['loc'].')';
        }

        $lines[] = '';
        $lines[] = '## Контакты';
        $lines[] = '';

        if (filled($site->org_email)) {
            $lines[] = '- Email: '.(string) $site->org_email;
        }

        if (filled($site->org_phone)) {
            $lines[] = '- Телефон: '.(string) $site->org_phone;
        }

        if (filled($site->org_street_address)) {
            $address = (string) $site->org_street_address;

            if (filled($site->org_address_locality)) {
                $address = (string) $site->org_address_locality.', '.$address;
            }

            $lines[] = '- Адрес: '.$address;
        }

        $extra = trim((string) ($site->llms_txt_extra ?? ''));

        if ($extra !== '') {
            $lines[] = '';
            $lines[] = $extra;
        }

        $lines[] = '';
        $lines[] = '## Дополнительно';
        $lines[] = '';
        $lines[] = '- Sitemap: '.$siteUrl.'/sitemap.xml';
        $lines[] = '- Политика конфиденциальности: '.$siteUrl.'/pages/privacy-policy';

        return implode("\n", $lines)."\n";
    }

    private function humanizePath(string $path): string
    {
        $slug = trim(str_replace(['/pages/', '/'], ['', ' '], $path));

        return $slug !== ''
            ? mb_convert_case(str_replace('-', ' ', $slug), MB_CASE_TITLE, 'UTF-8')
            : $path;
    }
}
