<?php

namespace App\Http\Controllers;

use App\Jobs\PrepareCommunityAiScenario;
use App\Models\CommunityAiScenario;
use App\Models\CommunityAiSource;
use App\Models\CommunityAiSourceMessage;
use App\Services\Community\CommunitySourceTextSanitizer;
use App\Services\SiteSettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CommunityAiBrowserCollectorController extends Controller
{
    public function __construct(
        private readonly SiteSettingsService $settings,
        private readonly CommunitySourceTextSanitizer $sanitizer,
    ) {}

    public function scenarios(Request $request): JsonResponse
    {
        $this->authorizeCollector($request);

        $scenarios = CommunityAiScenario::query()
            ->whereIn('status', [
                CommunityAiScenario::STATUS_DRAFT,
                CommunityAiScenario::STATUS_FAILED,
                CommunityAiScenario::STATUS_REVIEW,
            ])
            ->latest('id')
            ->limit(30)
            ->get()
            ->map(function (CommunityAiScenario $scenario): array {
                $sourceIds = collect($scenario->source_ids)->map(fn ($id): int => (int) $id)->all();
                $sources = CommunityAiSource::query()
                    ->whereIn('id', $sourceIds)
                    ->where('is_active', true)
                    ->orderBy('id')
                    ->get()
                    ->map(function (CommunityAiSource $source) use ($scenario): array {
                        $messagesCount = $source->messages()
                            ->whereBetween('sent_at', [$scenario->source_from, $scenario->source_to])
                            ->count();

                        return [
                            'id' => $source->id,
                            'name' => $source->name,
                            'chat_id' => $source->external_chat_id,
                            'public_url' => $source->public_url,
                            'messages_count' => $messagesCount,
                        ];
                    })
                    ->values();

                return [
                    'id' => $scenario->id,
                    'title' => $scenario->title ?: $scenario->source_post_title ?: 'Сценарий #'.$scenario->id,
                    'mode' => $scenario->mode,
                    'mode_label' => CommunityAiScenario::MODE_LABELS[$scenario->mode] ?? $scenario->mode,
                    'status' => $scenario->status,
                    'source_from' => $scenario->source_from->toIso8601String(),
                    'source_to' => $scenario->source_to->toIso8601String(),
                    'scan_keywords' => array_values($scenario->scan_keywords ?? []),
                    'sources' => $sources,
                ];
            })
            ->values();

        return response()->json(['scenarios' => $scenarios]);
    }

    public function storeMessages(Request $request): JsonResponse
    {
        $this->authorizeCollector($request);

        $data = $request->validate([
            'scenario_id' => ['required', 'integer', 'exists:community_ai_scenarios,id'],
            'chat_id' => ['required', 'string', 'max:100', 'regex:/^-?\d+$/'],
            'chat_name' => ['nullable', 'string', 'max:120'],
            'messages' => ['required', 'array', 'max:500'],
            'messages.*.external_id' => ['nullable', 'string', 'max:191'],
            'messages.*.sender_key' => ['nullable', 'string', 'size:64', 'regex:/^[a-f0-9]{64}$/'],
            'messages.*.text' => ['required', 'string', 'max:10000'],
            'messages.*.sent_at' => ['required', 'date'],
        ]);

        $scenario = CommunityAiScenario::query()->findOrFail((int) $data['scenario_id']);
        abort_unless(in_array($scenario->status, [
            CommunityAiScenario::STATUS_DRAFT,
            CommunityAiScenario::STATUS_FAILED,
            CommunityAiScenario::STATUS_REVIEW,
        ], true), 422, 'Сбор сообщений для этого сценария уже закрыт.');

        $sourceIds = collect($scenario->source_ids)->map(fn ($id): int => (int) $id)->all();
        $source = CommunityAiSource::query()
            ->whereKey($sourceIds)
            ->where('platform', 'max')
            ->where('external_chat_id', $data['chat_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $created = 0;
        $duplicates = 0;
        $outsideRange = 0;

        DB::transaction(function () use ($data, $scenario, $source, &$created, &$duplicates, &$outsideRange): void {
            foreach ($data['messages'] as $message) {
                $sentAt = CarbonImmutable::parse($message['sent_at']);
                if ($sentAt->lt($scenario->source_from) || $sentAt->gt($scenario->source_to)) {
                    $outsideRange++;

                    continue;
                }

                $text = Str::limit($this->sanitizer->sanitize((string) $message['text']), 10000, '');
                if ($text === '') {
                    continue;
                }

                $senderKey = filled($message['sender_key'] ?? null) ? (string) $message['sender_key'] : null;
                $externalId = trim((string) ($message['external_id'] ?? ''));
                if ($externalId === '') {
                    $externalId = hash('sha256', $source->id.'|'.$sentAt->getTimestampMs().'|'.$senderKey.'|'.$text);
                }

                $record = CommunityAiSourceMessage::query()->firstOrCreate(
                    [
                        'community_ai_source_id' => $source->id,
                        'external_message_id' => mb_substr($externalId, 0, 191),
                    ],
                    [
                        'sender_key' => $senderKey,
                        'sender_name' => null,
                        'text' => $text,
                        'content_hash' => hash('sha256', Str::lower($text)),
                        'sent_at' => $sentAt,
                        'raw_payload' => null,
                    ],
                );

                $record->wasRecentlyCreated ? $created++ : $duplicates++;
            }

            $source->update([
                'last_synced_at' => now(),
                'last_error' => null,
                'settings' => array_merge($source->settings ?? [], ['collection_mode' => 'browser']),
            ]);
        });

        return response()->json([
            'received' => count($data['messages']),
            'imported' => $created,
            'duplicates' => $duplicates,
            'outside_range' => $outsideRange,
        ]);
    }

    public function prepare(Request $request, int $scenario): JsonResponse
    {
        $this->authorizeCollector($request);

        $scenario = CommunityAiScenario::query()->findOrFail($scenario);
        abort_unless(in_array($scenario->status, [
            CommunityAiScenario::STATUS_DRAFT,
            CommunityAiScenario::STATUS_FAILED,
            CommunityAiScenario::STATUS_REVIEW,
        ], true), 422, 'Сценарий нельзя запустить из текущего статуса.');

        $messageCount = CommunityAiSourceMessage::query()
            ->whereIn('community_ai_source_id', collect($scenario->source_ids)->map(fn ($id): int => (int) $id))
            ->whereBetween('sent_at', [$scenario->source_from, $scenario->source_to])
            ->count();

        abort_if($messageCount === 0, 422, 'Сначала соберите сообщения хотя бы из одного чата.');

        $scenario->update([
            'status' => CommunityAiScenario::STATUS_QUEUED,
            'last_error' => null,
        ]);
        PrepareCommunityAiScenario::dispatch($scenario->id);

        return response()->json(['queued' => true, 'messages_count' => $messageCount]);
    }

    private function authorizeCollector(Request $request): void
    {
        $expected = $this->settings->communityAiCollectorToken();
        $provided = (string) $request->bearerToken();

        abort_unless(strlen($expected) >= 32 && hash_equals($expected, $provided), 401);
    }
}
