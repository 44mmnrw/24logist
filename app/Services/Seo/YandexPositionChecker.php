<?php

namespace App\Services\Seo;

use App\Models\SeoMonitoringSetting;
use Illuminate\Http\Client\Factory;
use RuntimeException;

class YandexPositionChecker
{
    public function __construct(private readonly Factory $http) {}

    /** @return array{position: ?int, url: ?string, results: int} */
    public function check(string $phrase, string $regionId = '225', string $device = 'DEVICE_ALL'): array
    {
        $settings = SeoMonitoringSetting::instance();
        $apiKey = trim((string) ($settings->yandex_api_key ?: config('seo-monitoring.yandex_api_key')));

        if ($apiKey === '') {
            throw new RuntimeException('YANDEX_SEARCH_API_KEY is not configured.');
        }

        $payload = [
            'query' => [
                'searchType' => 'SEARCH_TYPE_RU',
                'queryText' => $phrase,
                'familyMode' => 'FAMILY_MODE_MODERATE',
                'page' => '0',
                'fixTypoMode' => 'FIX_TYPO_MODE_OFF',
            ],
            'groupSpec' => [
                'groupMode' => 'GROUP_MODE_FLAT',
                'groupsOnPage' => (string) min(100, max(1, $settings->position_depth)),
                'docsInGroup' => '1',
            ],
            'maxPassages' => '1',
            'region' => $regionId,
            'l10n' => 'LOCALIZATION_RU',
            'responseFormat' => 'FORMAT_XML',
            'userAgent' => $this->userAgent($device),
        ];

        $folderId = trim((string) ($settings->yandex_folder_id ?: config('seo-monitoring.yandex_folder_id')));

        if ($folderId !== '') {
            $payload['folderId'] = $folderId;
        }

        $response = $this->http
            ->acceptJson()
            ->asJson()
            ->withHeaders(['Authorization' => 'Api-Key '.$apiKey])
            ->connectTimeout(10)
            ->timeout(60)
            ->retry([500, 1500], throw: false)
            ->post('https://searchapi.api.cloud.yandex.net/v2/web/search', $payload);

        if ($response->failed()) {
            throw new RuntimeException('Yandex Search API HTTP '.$response->status().': '.mb_substr($response->body(), 0, 1000));
        }

        $raw = base64_decode((string) $response->json('rawData'), true);

        if ($raw === false || trim($raw) === '') {
            throw new RuntimeException('Yandex Search API returned invalid rawData.');
        }

        $xml = simplexml_load_string($raw, options: LIBXML_NONET | LIBXML_NOCDATA);

        if ($xml === false) {
            throw new RuntimeException('Yandex Search API returned invalid XML.');
        }

        $documents = $xml->xpath('//group/doc') ?: [];
        $targetHost = mb_strtolower(trim($settings->target_host));

        foreach ($documents as $index => $document) {
            $url = trim((string) ($document->url ?? ''));
            $host = mb_strtolower((string) parse_url($url, PHP_URL_HOST));

            if ($host === $targetHost || str_ends_with($host, '.'.$targetHost)) {
                return ['position' => $index + 1, 'url' => $url, 'results' => count($documents)];
            }
        }

        return ['position' => null, 'url' => null, 'results' => count($documents)];
    }

    private function userAgent(string $device): string
    {
        return strtoupper($device) === 'DEVICE_PHONE'
            ? 'Mozilla/5.0 (Linux; Android 14; Mobile) AppleWebKit/537.36 Chrome/126 Mobile Safari/537.36'
            : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/126 Safari/537.36';
    }
}
