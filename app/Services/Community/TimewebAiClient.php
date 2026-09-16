<?php

namespace App\Services\Community;

use App\Models\CommunityAiPersona;
use App\Services\SiteSettingsService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use JsonException;
use RuntimeException;

final class TimewebAiClient
{
    public function __construct(private readonly SiteSettingsService $settings) {}

    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @return array{json: array<string, mixed>, content: string, usage: array<string, int>, raw: array<string, mixed>}
     */
    public function complete(CommunityAiPersona $persona, array $messages, int $maxTokens): array
    {
        $token = $this->settings->timewebAiToken();
        if ($token === '') {
            throw new RuntimeException('В настройках сайта не указан токен Timeweb AI.');
        }

        $baseUrl = rtrim($persona->provider_base_url, '/');
        $parts = parse_url($baseUrl);
        if (($parts['scheme'] ?? null) !== 'https' || ($parts['host'] ?? null) !== 'agent.timeweb.cloud') {
            throw new RuntimeException('Недопустимый API URL агента Timeweb.');
        }

        try {
            $response = Http::connectTimeout(10)
                ->timeout(90)
                ->retry(2, 750, throw: false)
                ->withToken($token)
                ->acceptJson()
                ->post($baseUrl.'/chat/completions', [
                    'messages' => $messages,
                    'max_completion_tokens' => max(100, min(4000, $maxTokens)),
                    'stream' => false,
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Не удалось соединиться с Timeweb AI.', previous: $exception);
        }

        if (! $response->successful()) {
            throw new RuntimeException('Timeweb AI отклонил запрос (HTTP '.$response->status().').');
        }

        $raw = $response->json();
        if (! is_array($raw)) {
            throw new RuntimeException('Timeweb AI вернул неверный ответ.');
        }

        $content = trim((string) data_get($raw, 'choices.0.message.content', ''));
        $json = $this->decodeJson($content);

        return [
            'json' => $json,
            'content' => $content,
            'usage' => [
                'prompt_tokens' => (int) data_get($raw, 'usage.prompt_tokens', 0),
                'completion_tokens' => (int) data_get($raw, 'usage.completion_tokens', 0),
            ],
            'raw' => Arr::only($raw, ['id', 'model', 'choices', 'usage']),
        ];
    }

    /** @return array<string, mixed> */
    private function decodeJson(string $content): array
    {
        $content = preg_replace('/^```(?:json)?\s*|\s*```$/iu', '', $content) ?? $content;

        try {
            $decoded = json_decode(trim($content), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Timeweb AI не вернул ожидаемый JSON.', previous: $exception);
        }

        if (! is_array($decoded)) {
            throw new RuntimeException('Timeweb AI вернул пустой JSON.');
        }

        return $decoded;
    }
}
