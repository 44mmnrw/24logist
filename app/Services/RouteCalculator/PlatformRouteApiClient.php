<?php

namespace App\Services\RouteCalculator;

use App\Services\SiteSettingsService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class PlatformRouteApiClient
{
    public function __construct(private readonly SiteSettingsService $settings) {}

    /** @param array<string, mixed> $query */
    public function get(string $endpoint, array $query): Response
    {
        return $this->request('get', $endpoint, $query);
    }

    /** @param array<string, mixed> $payload */
    public function post(string $endpoint, array $payload): Response
    {
        return $this->request('post', $endpoint, $payload);
    }

    /** @param array<string, mixed> $data */
    private function request(string $method, string $endpoint, array $data): Response
    {
        if (! $this->settings->routeApiConfigured()) {
            throw new RuntimeException('Калькулятор маршрута пока не настроен.');
        }

        $baseUrl = $this->settings->routeApiBaseUrl();
        $scheme = mb_strtolower((string) parse_url($baseUrl, PHP_URL_SCHEME));

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new RuntimeException('В настройках указан неверный адрес API платформы.');
        }

        if (app()->environment('production') && $scheme !== 'https') {
            throw new RuntimeException('В рабочем окружении API платформы должен использовать HTTPS.');
        }

        try {
            return Http::acceptJson()
                ->asJson()
                ->withToken($this->settings->routeApiSecret())
                ->withHeaders(['X-LogistRu-Client' => '24logist-site'])
                ->timeout($this->settings->routeApiTimeout())
                ->connectTimeout(min(5, $this->settings->routeApiTimeout()))
                ->when(app()->environment('local'), fn ($request) => $request->withoutVerifying())
                ->{$method}($baseUrl.'/'.ltrim($endpoint, '/'), $data);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Платформа расчёта маршрута не ответила. Попробуйте ещё раз.', previous: $exception);
        }
    }
}
