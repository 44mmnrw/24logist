<?php

namespace App\Services\Community;

use App\Models\CommunityAiSource;
use App\Models\CommunityAiSourceMessage;
use App\Services\SiteSettingsService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class MaxChatImportService
{
    private const ENDPOINT = 'https://platform-api2.max.ru/messages';

    public function __construct(
        private readonly SiteSettingsService $settings,
        private readonly MaxApiClient $api,
    ) {}

    public function import(CommunityAiSource $source, CarbonInterface $from, CarbonInterface $to): int
    {
        if ($source->platform !== 'max') {
            throw new RuntimeException('Поддерживаются только источники MAX.');
        }

        $token = $this->settings->maxBotToken();
        if ($token === '') {
            throw new RuntimeException('В настройках сайта не указан Bot Token MAX.');
        }

        $created = 0;
        $lowerBound = $from->getTimestampMs();
        $upperBound = $to->getTimestampMs();
        $cursor = $upperBound;

        try {
            for ($page = 0; $page < 50 && $cursor >= $lowerBound; $page++) {
                $response = $this->api->request()
                    ->connectTimeout(10)
                    ->timeout(30)
                    ->withHeaders(['Authorization' => $token])
                    ->get(self::ENDPOINT, [
                        'chat_id' => $source->external_chat_id,
                        // MAX names the upper time boundary `from` and the lower one `to`.
                        'from' => $cursor,
                        'to' => $lowerBound,
                        'count' => 100,
                    ]);

                if (! $response->successful()) {
                    throw new RuntimeException('MAX отклонил запрос сообщений (HTTP '.$response->status().').');
                }

                $messages = $response->json('messages');
                if (! is_array($messages) || $messages === []) {
                    break;
                }

                $oldestTimestamp = $cursor;
                foreach ($messages as $message) {
                    if (! is_array($message)) {
                        continue;
                    }

                    $timestamp = (int) ($message['timestamp'] ?? 0);
                    if ($timestamp <= 0) {
                        continue;
                    }

                    $oldestTimestamp = min($oldestTimestamp, $timestamp);
                    if ($timestamp < $lowerBound || $timestamp > $upperBound) {
                        continue;
                    }

                    $text = trim((string) data_get($message, 'body.text', ''));
                    if ($text === '') {
                        continue;
                    }

                    $externalId = $this->messageId($message, $timestamp, $text);
                    $senderId = (string) data_get($message, 'sender.user_id', '');
                    $senderName = $this->senderName((array) ($message['sender'] ?? []));

                    $record = CommunityAiSourceMessage::query()->firstOrCreate(
                        [
                            'community_ai_source_id' => $source->id,
                            'external_message_id' => $externalId,
                        ],
                        [
                            'sender_key' => $senderId !== '' ? hash('sha256', $source->id.'|'.$senderId) : null,
                            'sender_name' => $senderName !== '' ? $senderName : null,
                            'text' => $text,
                            'content_hash' => hash('sha256', Str::lower($text)),
                            'sent_at' => CarbonImmutable::createFromTimestampMs($timestamp),
                            'raw_payload' => Arr::only($message, ['timestamp', 'sender', 'recipient', 'body', 'link', 'url']),
                        ],
                    );

                    $created += $record->wasRecentlyCreated ? 1 : 0;
                }

                if (count($messages) < 100 || $oldestTimestamp >= $cursor) {
                    break;
                }

                $cursor = $oldestTimestamp - 1;
            }

            $source->update(['last_synced_at' => now(), 'last_error' => null]);

            return $created;
        } catch (Throwable $exception) {
            $source->update(['last_error' => mb_substr($exception->getMessage(), 0, 2000)]);
            throw $exception;
        }
    }

    /** @param array<string, mixed> $message */
    private function messageId(array $message, int $timestamp, string $text): string
    {
        foreach (['body.mid', 'message_id', 'id'] as $key) {
            $value = data_get($message, $key);
            if (is_string($value) && $value !== '') {
                return mb_substr($value, 0, 191);
            }
        }

        return hash('sha256', $timestamp.'|'.json_encode($message['sender'] ?? []).'|'.$text);
    }

    /** @param array<string, mixed> $sender */
    private function senderName(array $sender): string
    {
        $name = trim((string) ($sender['name'] ?? ''));
        if ($name !== '') {
            return $name;
        }

        return trim(implode(' ', array_filter([
            $sender['first_name'] ?? null,
            $sender['last_name'] ?? null,
        ], 'is_string')));
    }
}
