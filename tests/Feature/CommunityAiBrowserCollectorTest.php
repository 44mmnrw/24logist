<?php

namespace Tests\Feature;

use App\Jobs\PrepareCommunityAiScenario;
use App\Models\CommunityAiScenario;
use App\Models\CommunityAiSource;
use App\Models\CommunityAiSourceMessage;
use App\Models\SiteSetting;
use App\Services\SiteSettingsService;
use Database\Seeders\CommunityAiSourceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CommunityAiBrowserCollectorTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'collector-token-that-is-long-enough-123';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CommunityAiSourceSeeder::class);
        SiteSetting::instance()->update(['community_ai_collector_token' => self::TOKEN]);
        app(SiteSettingsService::class)->clearCache();
    }

    public function test_collector_requires_its_own_bearer_token(): void
    {
        $this->getJson(route('community.ai.collector.scenarios'))->assertUnauthorized();
    }

    public function test_extension_can_list_scenarios_and_import_anonymized_messages_idempotently(): void
    {
        $source = CommunityAiSource::query()->firstOrFail();
        $scenario = CommunityAiScenario::query()->create([
            'source_ids' => [$source->id],
            'source_from' => now()->subDay()->startOfDay(),
            'source_to' => now()->subDay()->endOfDay(),
            'scan_keywords' => ['ЭТрН'],
            'title' => 'Проверка браузерного импорта',
            'status' => CommunityAiScenario::STATUS_DRAFT,
        ]);

        $this->withToken(self::TOKEN)
            ->getJson(route('community.ai.collector.scenarios'))
            ->assertOk()
            ->assertJsonPath('scenarios.0.id', $scenario->id)
            ->assertJsonPath('scenarios.0.sources.0.chat_id', $source->external_chat_id)
            ->assertJsonPath('scenarios.0.scan_keywords.0', 'ЭТрН');

        $payload = [
            'scenario_id' => $scenario->id,
            'chat_id' => $source->external_chat_id,
            'chat_name' => $source->name,
            'messages' => [[
                'external_id' => 'browser-message-1',
                'sender_key' => hash('sha256', 'participant-1'),
                'text' => 'Проблема с ЭТрН, пишите test@example.com или +7 999 111-22-33',
                'sent_at' => now()->subDay()->setTime(12, 0)->toIso8601String(),
            ]],
        ];

        $this->withToken(self::TOKEN)
            ->postJson(route('community.ai.collector.messages'), $payload)
            ->assertOk()
            ->assertJsonPath('imported', 1);

        $this->withToken(self::TOKEN)
            ->postJson(route('community.ai.collector.messages'), $payload)
            ->assertOk()
            ->assertJsonPath('duplicates', 1);

        $this->assertDatabaseCount('community_ai_source_messages', 1);
        $message = CommunityAiSourceMessage::query()->firstOrFail();
        $this->assertStringContainsString('[email]', $message->text);
        $this->assertStringContainsString('[телефон]', $message->text);
        $this->assertNull($message->sender_name);

        $rawText = DB::table('community_ai_source_messages')->value('text');
        $this->assertNotSame($message->text, $rawText);
        $this->assertSame('browser', $source->fresh()->settings['collection_mode']);
    }

    public function test_extension_can_queue_a_scenario_after_collecting_messages(): void
    {
        Queue::fake();
        $source = CommunityAiSource::query()->firstOrFail();
        $scenario = CommunityAiScenario::query()->create([
            'source_ids' => [$source->id],
            'source_from' => now()->subDay()->startOfDay(),
            'source_to' => now()->subDay()->endOfDay(),
            'status' => CommunityAiScenario::STATUS_FAILED,
        ]);
        CommunityAiSourceMessage::query()->create([
            'community_ai_source_id' => $source->id,
            'external_message_id' => 'collected-message',
            'sender_key' => hash('sha256', 'participant'),
            'text' => 'Текст обсуждения',
            'content_hash' => hash('sha256', 'Текст обсуждения'),
            'sent_at' => now()->subDay()->setTime(12, 0),
        ]);

        $this->withToken(self::TOKEN)
            ->postJson(route('community.ai.collector.prepare', $scenario))
            ->assertOk()
            ->assertJsonPath('queued', true)
            ->assertJsonPath('messages_count', 1);

        $this->assertSame(CommunityAiScenario::STATUS_QUEUED, $scenario->fresh()->status);
        Queue::assertPushed(PrepareCommunityAiScenario::class, fn ($job): bool => $job->scenarioId === $scenario->id);

        $this->withToken(self::TOKEN)
            ->postJson(route('community.ai.collector.prepare', $scenario))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Сценарий нельзя запустить из текущего статуса.');
        Queue::assertPushed(PrepareCommunityAiScenario::class, 1);
    }

    public function test_source_post_scenarios_can_collect_context_and_queue_generation(): void
    {
        Queue::fake();
        $source = CommunityAiSource::query()->firstOrFail();
        $from = now()->subDay()->startOfDay();
        $to = now()->subDay()->endOfDay();
        $manualScenario = CommunityAiScenario::query()->create([
            'mode' => CommunityAiScenario::MODE_MANUAL,
            'source_ids' => [$source->id],
            'source_from' => $from,
            'source_to' => $to,
            'source_post_title' => 'Исходный пост',
            'manual_topic_body' => 'Полный текст поста из MAX.',
            'status' => CommunityAiScenario::STATUS_DRAFT,
        ]);

        $this->withToken(self::TOKEN)
            ->getJson(route('community.ai.collector.scenarios'))
            ->assertOk()
            ->assertJsonPath('scenarios.0.id', $manualScenario->id)
            ->assertJsonPath('scenarios.0.mode', CommunityAiScenario::MODE_MANUAL)
            ->assertJsonPath('scenarios.0.sources.0.chat_id', $source->external_chat_id);

        CommunityAiSourceMessage::query()->create([
            'community_ai_source_id' => $source->id,
            'external_message_id' => 'manual-collected-message',
            'sender_key' => hash('sha256', 'participant-manual'),
            'text' => 'Контекст обсуждения исходного поста.',
            'content_hash' => hash('sha256', 'manual-collected-message'),
            'sent_at' => $from->copy()->addHours(12),
        ]);

        $this->withToken(self::TOKEN)
            ->postJson(route('community.ai.collector.prepare', $manualScenario))
            ->assertOk()
            ->assertJsonPath('queued', true)
            ->assertJsonPath('messages_count', 1);

        $this->assertSame(CommunityAiScenario::STATUS_QUEUED, $manualScenario->fresh()->status);
        Queue::assertPushed(PrepareCommunityAiScenario::class, fn ($job): bool => $job->scenarioId === $manualScenario->id);
    }
}
