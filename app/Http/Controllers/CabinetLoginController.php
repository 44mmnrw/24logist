<?php

namespace App\Http\Controllers;

use App\Services\CabinetLoginClient;
use App\Services\SiteSettingsService;
use Illuminate\Http\Client\Response as PlatformResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

final class CabinetLoginController extends Controller
{
    private const HANDOFF_SESSION_KEY = 'cabinet_auth_handoff';

    public function __construct(
        private readonly SiteSettingsService $settings,
        private readonly CabinetLoginClient $client,
    ) {}

    public function login(Request $request): JsonResponse
    {
        if (! $this->settings->cabinetLoginConfigured()) {
            return $this->jsonError('Вход в личный кабинет пока не настроен.', 503);
        }

        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:1024'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        try {
            $response = $this->client->login(
                $validated['email'],
                $validated['password'],
                (bool) ($validated['remember'] ?? false),
                (string) $request->ip(),
            );

            if (! $response->successful()) {
                return $this->platformError($response, false);
            }

            return $this->storeHandoff($request, $this->client->extractHandoff($response));
        } catch (RuntimeException) {
            return $this->jsonError('Платформа временно недоступна. Попробуйте ещё раз.', 503);
        }
    }

    public function register(Request $request): JsonResponse
    {
        if (! $this->settings->cabinetRegistrationConfigured()) {
            return $this->jsonError('Регистрация личного кабинета пока не настроена.', 503);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'account_name' => ['required', 'string', 'max:255'],
            'inn' => ['required', 'string', 'regex:/^\d{10}(\d{2})?$/'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'max:1024', 'confirmed', Password::min(8)],
            'password_confirmation' => ['required', 'string', 'max:1024'],
            'capabilities' => ['required', 'array', 'min:1'],
            'capabilities.*' => ['string', Rule::in(['cargo_owner', 'forwarder'])],
            'terms_accepted' => ['accepted'],
            'privacy_policy_accepted' => ['accepted'],
        ], [
            'inn.regex' => 'ИНН должен содержать 10 или 12 цифр.',
            'capabilities.required' => 'Выберите хотя бы один вариант работы компании.',
            'terms_accepted.accepted' => 'Необходимо принять пользовательское соглашение.',
            'privacy_policy_accepted.accepted' => 'Необходимо принять политику обработки ПДн.',
        ]);

        $fields = [
            'name' => trim($validated['name']),
            'account_name' => trim($validated['account_name']),
            'inn' => preg_replace('/\D+/', '', $validated['inn']) ?? '',
            'email' => mb_strtolower(trim($validated['email'])),
            'phone' => trim($validated['phone']),
            'password' => $validated['password'],
            'password_confirmation' => $validated['password_confirmation'],
            'capabilities' => array_values(array_unique($validated['capabilities'])),
            'terms_accepted' => true,
            'privacy_policy_accepted' => true,
        ];

        try {
            $response = $this->client->register(
                $fields,
                (string) $request->ip(),
                mb_substr((string) $request->userAgent(), 0, 1024),
            );

            if (! $response->successful()) {
                return $this->platformError($response, true);
            }

            return $this->storeHandoff($request, $this->client->extractHandoff($response));
        } catch (RuntimeException) {
            return $this->jsonError('Платформа временно недоступна. Попробуйте ещё раз.', 503);
        }
    }

    public function partySuggestions(Request $request): JsonResponse
    {
        if (! $this->settings->cabinetRegistrationConfigured()) {
            return $this->jsonError('Регистрация личного кабинета пока не настроена.', 503);
        }

        $validated = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:120'],
        ]);

        try {
            $response = $this->client->suggestParty($validated['query'], (string) $request->ip());

            if ($response->status() === 404) {
                return response()->json(['suggestions' => []])->withHeaders($this->privateHeaders());
            }

            if (! $response->successful()) {
                return $this->jsonError('Подсказки организаций временно недоступны.', $response->status() === 429 ? 429 : 503);
            }

            return response()->json([
                'suggestions' => $this->partySuggestionPayload($response->json()),
            ])->withHeaders($this->privateHeaders());
        } catch (RuntimeException) {
            return $this->jsonError('Подсказки организаций временно недоступны.', 503);
        }
    }

    public function handoff(Request $request): Response
    {
        $handoff = $request->session()->pull(self::HANDOFF_SESSION_KEY);

        if (! is_array($handoff)
            || ! is_string($handoff['ticket'] ?? null)
            || ! is_string($handoff['handoff_url'] ?? null)
            || ! is_int($handoff['expires_at'] ?? null)
            || $handoff['expires_at'] < now()->getTimestamp()) {
            return response()->view('cabinet-auth-error', [
                'message' => 'Сеанс перехода истёк. Вернитесь на лендинг и повторите вход или регистрацию.',
            ], 410)->withHeaders($this->privateHeaders());
        }

        $scriptNonce = base64_encode(random_bytes(18));

        return response()->view('cabinet-auth-handoff', [
            'ticket' => $handoff['ticket'],
            'handoffUrl' => $handoff['handoff_url'],
            'scriptNonce' => $scriptNonce,
        ])->withHeaders(array_merge($this->privateHeaders(), [
            'Content-Security-Policy' => "default-src 'none'; script-src 'nonce-{$scriptNonce}'; style-src 'nonce-{$scriptNonce}'; form-action 'self' ".$this->client->platformOrigin()."; frame-ancestors 'none'; base-uri 'none'",
        ]));
    }

    /**
     * @param  array{ticket: string, handoff_url: string, expires_in: int}  $handoff
     */
    private function storeHandoff(Request $request, array $handoff): JsonResponse
    {
        $request->session()->put(self::HANDOFF_SESSION_KEY, [
            'ticket' => $handoff['ticket'],
            'handoff_url' => $handoff['handoff_url'],
            'expires_at' => now()->addSeconds($handoff['expires_in'])->getTimestamp(),
        ]);

        return response()->json([
            'handoff_url' => route('cabinet.handoff'),
            'method' => 'POST',
        ])->withHeaders($this->privateHeaders());
    }

    private function platformError(PlatformResponse $response, bool $registration): JsonResponse
    {
        $payload = $response->json();
        $code = is_array($payload) ? (string) ($payload['code'] ?? '') : '';

        if ($response->status() === 429) {
            $result = $this->jsonError('Слишком много попыток. Попробуйте позднее.', 429);
            $retryAfter = $response->header('Retry-After');

            return filled($retryAfter) ? $result->header('Retry-After', $retryAfter) : $result;
        }

        if ($response->status() === 401 && $code === 'invalid_credentials') {
            return $this->jsonError('Неверный email или пароль.', 422);
        }

        if ($response->status() === 422) {
            $validationErrors = $this->validationErrors($payload);
            $firstValidationMessage = collect($validationErrors)->flatten()->first();

            return response()->json([
                'message' => is_string($firstValidationMessage) && $firstValidationMessage !== ''
                    ? $firstValidationMessage
                    : ($registration ? 'Проверьте данные формы регистрации.' : 'Проверьте email и пароль.'),
                'errors' => $validationErrors,
            ], 422)->withHeaders($this->privateHeaders());
        }

        if (($response->status() === 401 && $code === 'invalid_client')
            || in_array($response->status(), [403, 503], true)) {
            return $this->jsonError('Интеграция с платформой временно недоступна.', 503);
        }

        return $this->jsonError('Платформа временно недоступна. Попробуйте ещё раз.', 502);
    }

    /** @return array<string, list<string>> */
    private function validationErrors(mixed $payload): array
    {
        if (! is_array($payload) || ! is_array($payload['errors'] ?? null)) {
            return [];
        }

        $allowedFields = [
            'name', 'account_name', 'inn', 'email', 'phone', 'password',
            'capabilities', 'terms_accepted', 'privacy_policy_accepted',
        ];
        $errors = [];

        foreach ($payload['errors'] as $field => $messages) {
            if (! is_string($field) || ! in_array($field, $allowedFields, true)) {
                continue;
            }

            $safeMessages = array_values(array_filter(
                array_map(static fn (mixed $message): string => is_string($message) ? $message : '', (array) $messages),
            ));

            if ($safeMessages !== []) {
                $errors[$field] = $safeMessages;
            }
        }

        return $errors;
    }

    /** @return list<array{inn: string, account_name: string}> */
    private function partySuggestionPayload(mixed $payload): array
    {
        if (! is_array($payload) || ! is_array($payload['suggestions'] ?? null)) {
            return [];
        }

        $suggestions = [];
        foreach (array_slice($payload['suggestions'], 0, 6) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $inn = preg_replace('/\D+/', '', (string) ($item['inn'] ?? '')) ?? '';
            $accountName = trim((string) ($item['account_name'] ?? ''));
            if (! in_array(strlen($inn), [10, 12], true) || $accountName === '' || mb_strlen($accountName) > 255) {
                continue;
            }

            $suggestions[] = ['inn' => $inn, 'account_name' => $accountName];
        }

        return $suggestions;
    }

    private function jsonError(string $message, int $status): JsonResponse
    {
        return response()->json(['message' => $message], $status)
            ->withHeaders($this->privateHeaders());
    }

    /** @return array<string, string> */
    private function privateHeaders(): array
    {
        return [
            'Cache-Control' => 'no-store, private',
            'Pragma' => 'no-cache',
            'Referrer-Policy' => 'strict-origin',
            'X-Robots-Tag' => 'noindex, nofollow',
        ];
    }
}
