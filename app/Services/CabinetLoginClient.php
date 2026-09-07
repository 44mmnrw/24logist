<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class CabinetLoginClient
{
    private const LOGIN_PATH = '/api/v1/landing-login/tickets';

    private const REGISTRATION_PATH = '/api/v1/landing-registration';

    private const PARTY_SUGGESTIONS_PATH = '/api/v1/landing-registration/party-suggestions';

    public function __construct(private readonly SiteSettingsService $settings) {}

    public function login(string $email, string $password, bool $remember, string $clientIp): Response
    {
        if (! $this->settings->cabinetLoginConfigured()) {
            throw new RuntimeException('Вход в личный кабинет пока не настроен.');
        }

        return $this->post(self::LOGIN_PATH, [
            'email' => mb_strtolower(trim($email)),
            'password' => $password,
            'remember' => $remember,
            'origin' => $this->settings->cabinetLoginOrigin(),
            'client_ip' => $clientIp,
        ]);
    }

    /**
     * @param array{
     *     name: string,
     *     account_name: string,
     *     inn: string,
     *     email: string,
     *     phone: string,
     *     password: string,
     *     password_confirmation: string,
     *     capabilities: list<string>,
     *     terms_accepted: bool,
     *     privacy_policy_accepted: bool
     * } $fields
     */
    public function register(array $fields, string $clientIp, string $clientUserAgent): Response
    {
        if (! $this->settings->cabinetRegistrationConfigured()) {
            throw new RuntimeException('Регистрация личного кабинета пока не настроена.');
        }

        return $this->post(self::REGISTRATION_PATH, array_merge($fields, [
            'origin' => $this->settings->cabinetLoginOrigin(),
            'client_ip' => $clientIp,
            'client_user_agent' => $clientUserAgent,
        ]));
    }

    public function suggestParty(string $query, string $clientIp): Response
    {
        if (! $this->settings->cabinetRegistrationConfigured()) {
            throw new RuntimeException('Регистрация личного кабинета пока не настроена.');
        }

        return $this->post(self::PARTY_SUGGESTIONS_PATH, [
            'query' => trim($query),
            'origin' => $this->settings->cabinetLoginOrigin(),
            'client_ip' => $clientIp,
        ]);
    }

    /**
     * @return array{ticket: string, handoff_url: string, expires_in: int}
     */
    public function extractHandoff(Response $response): array
    {
        $payload = $response->json();
        $ticket = is_array($payload) ? trim((string) ($payload['ticket'] ?? '')) : '';
        $handoffUrl = is_array($payload) ? trim((string) ($payload['handoff_url'] ?? '')) : '';
        $method = is_array($payload) ? mb_strtoupper(trim((string) ($payload['method'] ?? ''))) : '';
        $expiresIn = is_array($payload) ? (int) ($payload['expires_in'] ?? 0) : 0;

        if (preg_match('/^[a-f0-9]{64}$/D', $ticket) !== 1
            || $method !== 'POST'
            || ! hash_equals($this->expectedHandoffUrl(), $handoffUrl)
            || $expiresIn < 1
            || $expiresIn > 300) {
            throw new RuntimeException('Платформа вернула некорректные данные перехода.');
        }

        return [
            'ticket' => $ticket,
            'handoff_url' => $handoffUrl,
            'expires_in' => $expiresIn,
        ];
    }

    public function platformOrigin(): string
    {
        return SiteSettingsService::normalizeHttpsOrigin($this->settings->cabinetLoginUrl()) ?? '';
    }

    private function expectedHandoffUrl(): string
    {
        return $this->settings->cabinetLoginUrl().'/auth/landing/consume';
    }

    /** @param array<string, mixed> $payload */
    private function post(string $path, array $payload): Response
    {
        $baseUrl = $this->settings->cabinetLoginUrl();
        $host = (string) parse_url($baseUrl, PHP_URL_HOST);
        $port = parse_url($baseUrl, PHP_URL_PORT);
        $port = is_int($port) ? $port : 443;
        $connectIp = $this->settings->cabinetLoginConnectIp();

        if ($host === ''
            || SiteSettingsService::normalizeHttpsOrigin($baseUrl) === null
            || filter_var($connectIp, FILTER_VALIDATE_IP) === false) {
            throw new RuntimeException('В настройках указан неверный внутренний адрес платформы.');
        }

        try {
            return Http::acceptJson()
                ->asJson()
                ->withToken($this->settings->cabinetLoginApiSecret())
                ->timeout($this->settings->cabinetLoginApiTimeout())
                ->connectTimeout(min(5, $this->settings->cabinetLoginApiTimeout()))
                ->withOptions([
                    'allow_redirects' => false,
                    'verify' => $this->shouldVerifyTls($host),
                    'curl' => [
                        CURLOPT_PROXY => '',
                        CURLOPT_RESOLVE => [sprintf('%s:%d:%s', $host, $port, $connectIp)],
                    ],
                ])
                ->post($baseUrl.$path, $payload);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Платформа временно не отвечает. Попробуйте ещё раз.', previous: $exception);
        }
    }

    private function shouldVerifyTls(string $host): bool
    {
        return ! app()->environment('local') || ! str_ends_with(mb_strtolower($host), '.test');
    }
}
