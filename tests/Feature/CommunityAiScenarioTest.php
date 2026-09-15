<?php

namespace Tests\Feature;

use App\Jobs\PublishCommunityAiScenarioStep;
use App\Models\CommunityAiPersona;
use App\Models\CommunityAiScenario;
use App\Models\CommunityAiSource;
use App\Models\CommunityCategory;
use App\Models\CommunityPost;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\Community\CommunityAiScenarioPreparer;
use App\Services\Community\CommunityAiScenarioPublisher;
use App\Services\SiteSettingsService;
use Database\Seeders\CommunityAiPersonaSeeder;
use Database\Seeders\CommunityAiSourceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class CommunityAiScenarioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([CommunityAiPersonaSeeder::class, CommunityAiSourceSeeder::class]);
        SiteSetting::instance()->update([
            'community_max_bot_token' => 'max-test-token',
            'community_ai_timeweb_token' => 'timeweb-test-token',
        ]);
        app(SiteSettingsService::class)->clearCache();
    }

    public function test_it_imports_chat_builds_reviewable_drafts_and_schedules_approved_steps(): void
    {
        $source = CommunityAiSource::query()->firstOrFail();
        $from = now()->subDay()->startOfDay()->setMicrosecond(0);
        $to = now()->subDay()->endOfDay()->setMicrosecond(0);
        $aiCall = 0;

        Http::fake(function (HttpRequest $request) use ($from, &$aiCall) {
            if (str_starts_with($request->url(), 'https://platform-api2.max.ru/messages')) {
                return Http::response(['messages' => [[
                    'timestamp' => $from->copy()->addHours(12)->getTimestampMs(),
                    'sender' => ['user_id' => 123, 'name' => 'Private Person'],
                    'body' => ['mid' => 'max-message-1', 'text' => 'Truck waited six hours at loading. Contact test@example.com'],
                ]]]);
            }

            $aiCall++;
            $messages = $request->data()['messages'] ?? [];
            $userPrompt = collect($messages)->where('role', 'user')->pluck('content')->implode("\n");
            if ($aiCall === 1 && str_contains($userPrompt, '<chat_messages>')) {
                $content = [
                    'title' => 'Loading downtime',
                    'summary' => 'Drivers are waiting too long at loading.',
                    'question' => 'How should downtime be documented?',
                    'category_slug' => 'carriers',
                    'participants' => [
                        ['persona_slug' => 'sergey-fleet-owner', 'purpose' => 'Starts the practical topic', 'delay_minutes' => 0],
                        ['persona_slug' => 'anna-logistician', 'purpose' => 'Suggests an operating process', 'delay_minutes' => 20],
                        ['persona_slug' => 'elena-transport-lawyer', 'purpose' => 'Explains document evidence', 'delay_minutes' => 55],
                    ],
                ];
            } elseif ($aiCall === 2) {
                $content = ['action' => 'topic', 'title' => 'How to document loading downtime', 'body' => 'A truck spent six hours waiting. How do you record and charge this time?', 'needs_review' => true, 'reason' => 'practical question'];
            } else {
                $content = ['action' => 'comment', 'title' => null, 'body' => 'Record arrival and departure times and have the responsible party confirm them.', 'needs_review' => true, 'reason' => 'adds a concrete step'];
            }

            return Http::response([
                'id' => 'generation-test',
                'model' => 'GPT-5.4 Mini',
                'choices' => [['message' => ['content' => json_encode($content, JSON_UNESCAPED_UNICODE)]]],
                'usage' => ['prompt_tokens' => 50, 'completion_tokens' => 20],
            ]);
        });

        $scenario = CommunityAiScenario::query()->create([
            'source_ids' => [$source->id],
            'source_from' => $from,
            'source_to' => $to,
            'scan_keywords' => ['Truck', 'loading'],
            'status' => CommunityAiScenario::STATUS_DRAFT,
        ]);

        app(CommunityAiScenarioPreparer::class)->prepare($scenario);

        $scenario->refresh();
        $this->assertSame(CommunityAiScenario::STATUS_REVIEW, $scenario->status);
        $this->assertSame('carriers', $scenario->category->slug);
        $this->assertSame(['Truck', 'loading'], $scenario->scan_keywords);
        $this->assertSame(1, $scenario->settings['keyword_match_count']);
        $this->assertCount(3, $scenario->steps);
        $this->assertSame('topic', $scenario->steps->first()->type);
        $this->assertTrue($scenario->steps->every(fn ($step): bool => $step->status === 'pending_review'));
        $this->assertDatabaseCount('community_ai_source_messages', 1);
        $this->assertDatabaseCount('community_ai_generations', 4);
        $encryptedText = (string) DB::table('community_ai_source_messages')->value('text');
        $this->assertStringNotContainsString('test@example.com', $encryptedText);

        Http::assertSent(function (HttpRequest $request) use ($from, $to): bool {
            if (! str_starts_with($request->url(), 'https://platform-api2.max.ru/messages')) {
                return false;
            }

            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return (int) ($query['from'] ?? 0) === $from->getTimestampMs()
                && (int) ($query['to'] ?? 0) === $to->getTimestampMs()
                && $request->hasHeader('Authorization', 'max-test-token');
        });
        Http::assertSent(function (HttpRequest $request): bool {
            if (! str_contains($request->url(), 'agent.timeweb.cloud')) {
                return false;
            }

            return isset($request->data()['max_completion_tokens'])
                && ! isset($request->data()['temperature'])
                && $request->hasHeader('Authorization', 'Bearer timeweb-test-token');
        });
        Http::assertSent(function (HttpRequest $request): bool {
            if (! str_contains($request->url(), 'agent.timeweb.cloud')) {
                return false;
            }

            $prompt = collect($request->data()['messages'] ?? [])->pluck('content')->implode("\n");

            return str_contains($prompt, '<chat_messages>')
                && str_contains($prompt, 'Ключевые слова фокуса: truck, loading')
                && ! str_contains($prompt, 'test@example.com')
                && str_contains($prompt, '[email]');
        });

        $scenario->steps()->update(['status' => 'approved']);
        $scenario->update(['status' => CommunityAiScenario::STATUS_APPROVED]);
        Queue::fake();
        app(CommunityAiScenarioPublisher::class)->schedule($scenario->fresh());

        $this->assertSame(CommunityAiScenario::STATUS_SCHEDULED, $scenario->fresh()->status);
        Queue::assertPushed(PublishCommunityAiScenarioStep::class, 3);
    }

    public function test_admin_can_open_scenario_workflow_page(): void
    {
        $scenario = CommunityAiScenario::query()->create([
            'source_ids' => [CommunityAiSource::query()->value('id')],
            'source_from' => now()->subDay(),
            'source_to' => now(),
            'status' => CommunityAiScenario::STATUS_DRAFT,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('filament.admin.resources.community-ai-scenarios.edit', $scenario))
            ->assertOk()
            ->assertSeeText('Сканировать и создать черновики');
    }

    public function test_it_stops_before_generation_when_keywords_do_not_match_messages(): void
    {
        $source = CommunityAiSource::query()->firstOrFail();
        $from = now()->subDay()->startOfDay()->setMicrosecond(0);
        $to = now()->subDay()->endOfDay()->setMicrosecond(0);

        Http::fake([
            'https://platform-api2.max.ru/messages*' => Http::response(['messages' => [[
                'timestamp' => $from->copy()->addHours(12)->getTimestampMs(),
                'sender' => ['user_id' => 123, 'name' => 'Private Person'],
                'body' => ['mid' => 'max-message-without-match', 'text' => 'Routine discussion about fuel receipts.'],
            ]]]),
            '*' => Http::response([], 500),
        ]);

        $scenario = CommunityAiScenario::query()->create([
            'source_ids' => [$source->id],
            'source_from' => $from,
            'source_to' => $to,
            'scan_keywords' => ['ЭТрН'],
            'status' => CommunityAiScenario::STATUS_DRAFT,
        ]);

        try {
            app(CommunityAiScenarioPreparer::class)->prepare($scenario);
            $this->fail('Preparation should fail when keywords have no matches.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'За выбранный период не найдено сообщений по заданным ключевым словам.',
                $exception->getMessage(),
            );
        }

        $this->assertSame(CommunityAiScenario::STATUS_FAILED, $scenario->fresh()->status);
        $this->assertDatabaseCount('community_ai_generations', 0);
    }

    public function test_publisher_creates_topic_then_comments_idempotently(): void
    {
        $personas = CommunityAiPersona::query()->take(2)->get();
        $scenario = CommunityAiScenario::query()->create([
            'community_category_id' => CommunityCategory::query()->where('slug', 'general')->value('id'),
            'source_ids' => [CommunityAiSource::query()->value('id')],
            'source_from' => now()->subDay(),
            'source_to' => now(),
            'status' => CommunityAiScenario::STATUS_SCHEDULED,
        ]);
        $topic = $scenario->steps()->create([
            'community_ai_persona_id' => $personas[0]->id,
            'type' => 'topic',
            'sequence' => 1,
            'planned_delay_minutes' => 0,
            'draft_title' => 'Scenario topic',
            'draft_body' => 'Scenario topic body',
            'status' => 'scheduled',
            'idempotency_key' => 'scenario-test-topic',
        ]);
        $comment = $scenario->steps()->create([
            'community_ai_persona_id' => $personas[1]->id,
            'type' => 'comment',
            'sequence' => 2,
            'planned_delay_minutes' => 10,
            'draft_body' => 'Scenario comment body',
            'status' => 'scheduled',
            'idempotency_key' => 'scenario-test-comment',
        ]);

        app()->call([new PublishCommunityAiScenarioStep($topic->id), 'handle']);
        app()->call([new PublishCommunityAiScenarioStep($topic->id), 'handle']);
        app()->call([new PublishCommunityAiScenarioStep($comment->id), 'handle']);

        $this->assertDatabaseCount('community_posts', 1);
        $this->assertDatabaseCount('community_comments', 1);
        $this->assertSame(1, CommunityPost::query()->firstOrFail()->comments_count);
        $this->assertSame(CommunityAiScenario::STATUS_COMPLETED, $scenario->fresh()->status);
    }
}
