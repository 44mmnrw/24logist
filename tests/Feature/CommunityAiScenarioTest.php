<?php

namespace Tests\Feature;

use App\Filament\Resources\CommunityAiScenarios\CommunityAiScenarioResource;
use App\Filament\Resources\CommunityAiScenarios\Pages\CreateCommunityAiScenario;
use App\Filament\Resources\CommunityAiScenarios\Pages\EditCommunityAiScenario;
use App\Jobs\PublishCommunityAiScenarioStep;
use App\Models\CommunityAiGeneration;
use App\Models\CommunityAiPersona;
use App\Models\CommunityAiScenario;
use App\Models\CommunityAiSource;
use App\Models\CommunityAiSourceMessage;
use App\Models\CommunityCategory;
use App\Models\CommunityComment;
use App\Models\CommunityPost;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\Community\CommunityAiScenarioPreparer;
use App\Services\Community\CommunityAiScenarioPublisher;
use App\Services\Community\TimewebAiClient;
use App\Services\SiteSettingsService;
use Database\Seeders\CommunityAiPersonaSeeder;
use Database\Seeders\CommunityAiSourceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
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
        $source->update(['settings' => ['collection_mode' => 'bot_api']]);
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
                        ['persona_slug' => 'mikhail-driver', 'purpose' => 'Asks about evidence from the loading point', 'delay_minutes' => 80],
                        ['persona_slug' => 'igor-forwarder', 'purpose' => 'Replies to Anna about the responsible party', 'delay_minutes' => 110, 'reply_to' => 'anna-logistician'],
                    ],
                ];
            } elseif ($aiCall === 2) {
                $content = ['action' => 'topic', 'title' => 'How to document loading downtime', 'body' => 'A truck spent six hours waiting. How do you record and charge this time?', 'needs_review' => true, 'reason' => 'practical question'];
            } else {
                $bodies = [
                    3 => 'Record arrival and departure times and have the responsible party confirm them.',
                    4 => 'Check which timestamp the warehouse accepts before the truck leaves.',
                    5 => 'Can the driver photograph the signed waiting note at the loading point?',
                    6 => 'That works only if the named warehouse employee confirms the record.',
                ];
                $content = ['action' => 'comment', 'title' => null, 'body' => $bodies[$aiCall], 'needs_review' => true, 'reason' => 'adds one conversational move'];
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
        $this->assertCount(5, $scenario->steps);
        $this->assertSame('topic', $scenario->steps->first()->type);
        $this->assertTrue($scenario->steps->every(fn ($step): bool => $step->status === 'pending_review'));
        $this->assertSame(3, $scenario->steps()->where('type', 'comment')->whereNull('parent_step_id')->count());
        $this->assertSame(1, $scenario->steps()->where('type', 'comment')->whereNotNull('parent_step_id')->count());
        $commentSteps = $scenario->steps()->where('type', 'comment')->get();
        $this->assertTrue($commentSteps->every(fn ($step): bool => in_array($step->conversation_move, [
            'question', 'agree', 'disagree', 'clarify', 'correct', 'doubt', 'practical_detail', 'support', 'light_humor', 'partial_answer',
        ], true)));
        $this->assertTrue($commentSteps->every(fn ($step): bool => $step->target_word_count >= 5 && $step->target_word_count <= 60));
        $this->assertDatabaseCount('community_ai_source_messages', 1);
        $this->assertDatabaseCount('community_ai_generations', 6);
        $encryptedText = (string) DB::table('community_ai_source_messages')->value('text');
        $this->assertStringNotContainsString('test@example.com', $encryptedText);

        Http::assertSent(function (HttpRequest $request) use ($from, $to): bool {
            if (! str_starts_with($request->url(), 'https://platform-api2.max.ru/messages')) {
                return false;
            }

            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return (int) ($query['from'] ?? 0) === $to->getTimestampMs()
                && (int) ($query['to'] ?? 0) === $from->getTimestampMs()
                && $request->hasHeader('Authorization', 'max-test-token');
        });
        Http::assertSent(function (HttpRequest $request): bool {
            if (! str_contains($request->url(), 'agent.timeweb.cloud')) {
                return false;
            }

            return isset($request->data()['max_completion_tokens'])
                && ! isset($request->data()['temperature'])
                && ! isset($request->data()['model'])
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
        Http::assertSent(function (HttpRequest $request): bool {
            if (! str_contains($request->url(), 'agent.timeweb.cloud')) {
                return false;
            }

            $prompt = collect($request->data()['messages'] ?? [])->pluck('content')->implode("\n");

            return str_contains($prompt, 'Профиль грамотности персонажа:')
                && str_contains($prompt, 'Режим письма для этой реплики:');
        });

        $historicalDate = now()->subMonth()->startOfHour();
        $topicDate = $historicalDate->copy()->subHour();
        $scenario->steps()->update(['status' => 'approved']);
        $scenario->steps()->first()->update(['scheduled_at' => $topicDate]);
        $scenario->update(['status' => CommunityAiScenario::STATUS_APPROVED, 'planned_at' => $historicalDate]);
        Queue::fake();
        app(CommunityAiScenarioPublisher::class)->schedule($scenario->fresh());

        $this->assertSame(CommunityAiScenario::STATUS_SCHEDULED, $scenario->fresh()->status);
        $this->assertSame($topicDate->toDateTimeString(), $scenario->steps()->first()->scheduled_at->toDateTimeString());
        $this->assertSame($historicalDate->copy()->addMinutes(20)->toDateTimeString(), $scenario->steps()->skip(1)->first()->scheduled_at->toDateTimeString());
        Queue::assertPushed(PublishCommunityAiScenarioStep::class, 5);
    }

    public function test_admin_can_create_a_manual_topic_scenario(): void
    {
        $persona = CommunityAiPersona::query()->where('can_create_posts', true)->firstOrFail();
        $category = CommunityCategory::query()->firstOrFail();
        $source = CommunityAiSource::query()->firstOrFail();
        $from = now()->subDay()->startOfDay();
        $to = now()->subDay()->endOfDay();
        $this->actingAs(User::factory()->create());

        $this->get(CommunityAiScenarioResource::getUrl('create'))
            ->assertOk()
            ->assertSeeText('Пост из чата + обсуждение');

        Livewire::test(CreateCommunityAiScenario::class)
            ->fillForm([
                'mode' => CommunityAiScenario::MODE_MANUAL,
                'topic_persona_id' => $persona->id,
                'community_category_id' => $category->id,
                'source_ids' => [$source->id],
                'source_from' => $from,
                'source_to' => $to,
                'source_post_title' => 'Тема редактора',
                'manual_topic_body' => 'Исходный пост, по которому нужно создать новую тему.',
                'editor_brief' => 'Обсудить риски для разных сторон.',
                'planned_at' => now()->addHour(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $scenario = CommunityAiScenario::query()->latest('id')->firstOrFail();
        $this->assertSame(CommunityAiScenario::MODE_MANUAL, $scenario->mode);
        $this->assertSame($persona->id, $scenario->topic_persona_id);
        $this->assertNull($scenario->title);
        $this->assertSame('Тема редактора', $scenario->source_post_title);
        $this->assertSame('Исходный пост, по которому нужно создать новую тему.', $scenario->manual_topic_body);
        $this->assertSame([$source->id], $scenario->source_ids);
    }

    public function test_source_post_is_rewritten_and_comments_use_collected_discussion(): void
    {
        $personas = CommunityAiPersona::query()->with('communityUser')->take(5)->get();
        $topicPersona = $personas->first();
        $commenters = $personas->skip(1)->values();
        $source = CommunityAiSource::query()->firstOrFail();
        $from = now()->subDay()->startOfDay();
        $to = now()->subDay()->endOfDay();
        CommunityAiSourceMessage::query()->create([
            'community_ai_source_id' => $source->id,
            'external_message_id' => 'manual-context-1',
            'sender_key' => hash('sha256', 'participant-1'),
            'text' => 'В чате спорили, можно ли отозвать уже подписанный документ.',
            'content_hash' => hash('sha256', 'manual-context-1'),
            'sent_at' => $from->copy()->addHours(12),
        ]);
        $aiCall = 0;
        $planPrompt = '';
        $commentPrompt = '';

        Http::fake(function (HttpRequest $request) use ($commenters, &$aiCall, &$planPrompt, &$commentPrompt) {
            $this->assertStringContainsString('agent.timeweb.cloud', $request->url());
            $aiCall++;
            $prompt = collect($request->data()['messages'] ?? [])->pluck('content')->implode("\n");

            if (str_contains($prompt, 'Доступные комментаторы:')) {
                $planPrompt = $prompt;
                $content = [
                    'topic_title' => 'Как вернуть документ на исправление',
                    'discussion_summary' => 'Люди спорили, можно ли отозвать уже подписанный документ.',
                    'participants' => [
                        ['persona_slug' => $commenters[0]->slug, 'purpose' => 'Даёт практическую оценку', 'delay_minutes' => 11],
                        ['persona_slug' => $commenters[1]->slug, 'purpose' => 'Проверяет документы', 'delay_minutes' => 27],
                        ['persona_slug' => $commenters[2]->slug, 'purpose' => 'Задаёт вопрос о статусе', 'delay_minutes' => 43],
                        ['persona_slug' => $commenters[3]->slug, 'purpose' => 'Отвечает на первый комментарий', 'delay_minutes' => 61, 'reply_to' => $commenters[0]->slug],
                    ],
                ];
            } elseif ($aiCall === 2) {
                $content = [
                    'action' => 'topic',
                    'title' => 'Документ ушёл с ошибкой',
                    'body' => 'Ошибку заметили после подписания документа. Можно ли вернуть его на исправление?',
                    'needs_review' => true,
                    'reason' => 'Пересказывает исходный пост',
                ];
            } else {
                $commentPrompt = $prompt;
                $bodies = [
                    3 => 'Уточните у получателя, в каком разделе находится документ.',
                    4 => 'Если документ уже оформлен, отправителю проще отозвать его и прислать заново.',
                    5 => 'Какой статус документа сейчас видит вторая сторона?',
                    6 => 'Да, но сначала стоит получить от получателя снимок экрана со статусом.',
                ];
                $content = [
                    'action' => 'comment',
                    'title' => null,
                    'body' => $bodies[$aiCall],
                    'needs_review' => true,
                    'reason' => 'Добавляет новый взгляд',
                ];
            }

            return Http::response([
                'id' => 'manual-generation-'.$aiCall,
                'model' => 'GPT-5.4 Mini',
                'choices' => [['message' => ['content' => json_encode($content, JSON_UNESCAPED_UNICODE)]]],
                'usage' => ['prompt_tokens' => 30, 'completion_tokens' => 15],
            ]);
        });

        $scenario = CommunityAiScenario::query()->create([
            'mode' => CommunityAiScenario::MODE_MANUAL,
            'topic_persona_id' => $topicPersona->id,
            'community_category_id' => CommunityCategory::query()->firstOrFail()->id,
            'source_ids' => [$source->id],
            'source_from' => $from,
            'source_to' => $to,
            'source_post_title' => 'Исходный заголовок',
            'manual_topic_body' => "Первый абзац.\n\nВторой абзац и вопрос?",
            'editor_brief' => 'Нужны разные позиции.',
            'status' => CommunityAiScenario::STATUS_DRAFT,
        ]);

        app(CommunityAiScenarioPreparer::class)->prepare($scenario);

        $scenario->refresh();
        $steps = $scenario->steps()->orderBy('sequence')->get();
        $this->assertSame(CommunityAiScenario::STATUS_REVIEW, $scenario->status);
        $this->assertCount(5, $steps);
        $this->assertSame('topic', $steps[0]->type);
        $this->assertSame($topicPersona->id, $steps[0]->community_ai_persona_id);
        $this->assertSame('Документ ушёл с ошибкой', $steps[0]->draft_title);
        $this->assertSame('Ошибку заметили после подписания документа. Можно ли вернуть его на исправление?', $steps[0]->draft_body);
        $this->assertSame('pending_review', $steps[0]->status);
        $this->assertSame('comment', $steps[1]->type);
        $this->assertSame('comment', $steps[2]->type);
        $this->assertSame('comment', $steps[3]->type);
        $this->assertSame('comment', $steps[4]->type);
        $this->assertNull($steps[1]->parent_step_id);
        $this->assertSame($steps[1]->id, $steps[4]->parent_step_id);
        $this->assertSame(6, $aiCall);
        $this->assertStringContainsString('<source_post>', $planPrompt);
        $this->assertStringContainsString('В чате спорили, можно ли отозвать', $planPrompt);
        $this->assertStringContainsString('Люди спорили, можно ли отозвать', $commentPrompt);
        $this->assertStringContainsString('Ориентир длины:', $commentPrompt);
        $this->assertStringContainsString('Ты отвечаешь именно на комментарий', $commentPrompt);
        $this->assertStringContainsString('Уточните у получателя', $commentPrompt);
        $this->assertStringContainsString('Какой статус документа', $commentPrompt);
        $this->assertStringNotContainsString('Если документ уже оформлен', $commentPrompt);
        $this->assertStringNotContainsString('Всё обсуждение до тебя', $commentPrompt);
        Http::assertNotSent(fn (HttpRequest $request): bool => str_starts_with($request->url(), 'https://platform-api2.max.ru'));
    }

    public function test_overformal_comment_is_retried_with_conversational_style_feedback(): void
    {
        $personas = CommunityAiPersona::query()->with('communityUser')->take(5)->get();
        $topicPersona = $personas->first();
        $commenters = $personas->skip(1)->values();
        $source = CommunityAiSource::query()->firstOrFail();
        $from = now()->subDay()->startOfDay();
        $to = now()->subDay()->endOfDay();
        CommunityAiSourceMessage::query()->create([
            'community_ai_source_id' => $source->id,
            'external_message_id' => 'style-context-1',
            'sender_key' => hash('sha256', 'participant-style'),
            'text' => 'Получатель не может поправить заказчика в документе.',
            'content_hash' => hash('sha256', 'style-context-1'),
            'sent_at' => $from->copy()->addHours(12),
        ]);
        $aiCall = 0;
        $retryPrompt = '';
        $retryLastRole = '';

        Http::fake(function (HttpRequest $request) use ($commenters, &$aiCall, &$retryPrompt, &$retryLastRole) {
            $aiCall++;
            $messages = $request->data()['messages'] ?? [];
            $prompt = collect($messages)->pluck('content')->implode("\n");

            if (str_contains($prompt, 'Доступные комментаторы:')) {
                $content = [
                    'topic_title' => 'Поле заказчика нельзя изменить',
                    'discussion_summary' => 'В чате уточняли статус документа и можность вернуть его отправителю.',
                    'participants' => [
                        ['persona_slug' => $commenters[0]->slug, 'purpose' => 'Задаёт один вопрос о статусе документа', 'delay_minutes' => 10],
                        ['persona_slug' => $commenters[1]->slug, 'purpose' => 'Коротко предлагает вернуть документ отправителю', 'delay_minutes' => 25],
                        ['persona_slug' => $commenters[2]->slug, 'purpose' => 'Уточняет, что видит получатель', 'delay_minutes' => 40],
                        ['persona_slug' => $commenters[3]->slug, 'purpose' => 'Отвечает на первый комментарий', 'delay_minutes' => 60, 'reply_to' => $commenters[0]->slug],
                    ],
                ];
            } elseif ($aiCall === 2) {
                $content = [
                    'action' => 'topic',
                    'title' => 'Не даёт изменить заказчика',
                    'body' => 'Получатель видит документ, но поле заказчика заблокировано. Где искать причину?',
                    'needs_review' => true,
                    'reason' => 'Создаёт тему',
                ];
            } elseif ($aiCall === 3) {
                $content = [
                    'action' => 'comment',
                    'title' => null,
                    'body' => 'Я бы сначала разделила технический маршрут — и юридическое содержание. В данном случае вывод такой: необходимо определить полномочия; всех участников процесса.',
                    'needs_review' => true,
                    'reason' => 'Даёт полный анализ',
                ];
            } elseif ($aiCall === 4) {
                $retryPrompt = $prompt;
                $retryLastRole = (string) data_get($messages, (count($messages) - 1).'.role');
                $content = [
                    'action' => 'comment',
                    'title' => null,
                    'body' => 'А документ сейчас во входящих — или уже в архиве: это видно; у получателя?',
                    'needs_review' => true,
                    'reason' => 'Задаёт один уточняющий вопрос',
                ];
            } elseif ($aiCall === 5) {
                $content = [
                    'action' => 'comment',
                    'title' => null,
                    'body' => 'Если он уже оформлен, проще вернуть отправителю на исправление.',
                    'needs_review' => true,
                    'reason' => 'Предлагает один шаг',
                ];
            } elseif ($aiCall === 6) {
                $content = [
                    'action' => 'comment',
                    'title' => null,
                    'body' => 'А какой статус сейчас написан у получателя рядом с документом?',
                    'needs_review' => true,
                    'reason' => 'Уточняет одну деталь',
                ];
            } else {
                $content = [
                    'action' => 'comment',
                    'title' => null,
                    'body' => 'Вот статус как раз и покажет, можно ли ещё править этот вариант.',
                    'needs_review' => true,
                    'reason' => 'Отвечает на предыдущую реплику',
                ];
            }

            return Http::response([
                'id' => 'style-generation-'.$aiCall,
                'model' => 'GPT-5.4 Mini',
                'choices' => [['message' => ['content' => json_encode($content, JSON_UNESCAPED_UNICODE)]]],
                'usage' => ['prompt_tokens' => 30, 'completion_tokens' => 15],
            ]);
        });

        $scenario = CommunityAiScenario::query()->create([
            'mode' => CommunityAiScenario::MODE_MANUAL,
            'topic_persona_id' => $topicPersona->id,
            'community_category_id' => CommunityCategory::query()->firstOrFail()->id,
            'source_ids' => [$source->id],
            'source_from' => $from,
            'source_to' => $to,
            'source_post_title' => 'Документ нельзя отредактировать',
            'manual_topic_body' => 'У получателя заблокировано поле заказчика. Что проверить?',
            'status' => CommunityAiScenario::STATUS_DRAFT,
        ]);

        app(CommunityAiScenarioPreparer::class)->prepare($scenario);

        $comments = $scenario->steps()->where('type', 'comment')->orderBy('sequence')->pluck('draft_body')->all();
        $this->assertSame(7, $aiCall);
        $this->assertSame('А документ сейчас во входящих, или уже в архиве, это видно, у получателя?', $comments[0]);
        $this->assertDoesNotMatchRegularExpression('/[\x{00AD}\x{2010}-\x{2015}\x{2212}:;]/u', $comments[0]);
        $this->assertSame('user', $retryLastRole);
        $this->assertStringContainsString('шаблонное вступление', $retryPrompt);
        $this->assertStringContainsString('канцелярит', $retryPrompt);
        $this->assertStringContainsString('использовано длинное тире', $retryPrompt);
        $this->assertStringContainsString('использовано двоеточие', $retryPrompt);
        $this->assertStringContainsString('использована точка с запятой', $retryPrompt);
        $this->assertDatabaseHas('community_ai_generations', [
            'community_ai_scenario_id' => $scenario->id,
            'community_ai_scenario_step_id' => $scenario->steps()->where('type', 'comment')->orderBy('sequence')->value('id'),
            'purpose' => 'comment_retry',
            'status' => 'succeeded',
        ]);
    }

    public function test_comment_style_allows_an_ordinary_hyphen_but_rejects_a_long_dash(): void
    {
        $service = app(CommunityAiScenarioPreparer::class);
        $violationsMethod = new \ReflectionMethod($service, 'commentStyleViolations');
        $normalizeMethod = new \ReflectionMethod($service, 'normalizeGeneratedPunctuation');

        $ordinaryHyphenViolations = $violationsMethod->invoke($service, 'А у других как-то иначе?', []);
        $longDashViolations = $violationsMethod->invoke($service, 'Сверили документы — даты разошлись.', []);

        $this->assertNotContains('использовано длинное тире', $ordinaryHyphenViolations);
        $this->assertContains('использовано длинное тире', $longDashViolations);
        $this->assertSame('А у других как-то иначе?', $normalizeMethod->invoke($service, 'А у других как-то иначе?'));
        $this->assertSame('Сверили документы, даты разошлись.', $normalizeMethod->invoke($service, 'Сверили документы — даты разошлись.'));
    }

    public function test_timeweb_error_message_is_included_without_raw_response_data(): void
    {
        $persona = CommunityAiPersona::query()->firstOrFail();
        Http::fake([
            '*' => Http::response([
                'error' => ['message' => '<b>messages: system role after assistant is not allowed</b>'],
                'debug' => ['secret' => 'must-not-be-exposed'],
            ], 400),
        ]);

        try {
            app(TimewebAiClient::class)->complete(
                $persona,
                [['role' => 'user', 'content' => 'Тест']],
                500,
            );
            $this->fail('Timeweb client should throw on HTTP 400.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Timeweb AI отклонил запрос (HTTP 400): messages: system role after assistant is not allowed',
                $exception->getMessage(),
            );
            $this->assertStringNotContainsString('must-not-be-exposed', $exception->getMessage());
        }
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
            ->assertSeeText('Сканировать и создать черновики')
            ->assertSeeText('Очистить результаты')
            ->assertSeeText('Удалить сценарий');
    }

    public function test_admin_can_clear_scenario_results_without_removing_inputs_or_source_messages(): void
    {
        $source = CommunityAiSource::query()->firstOrFail();
        $persona = CommunityAiPersona::query()->firstOrFail();
        $scenario = CommunityAiScenario::query()->create([
            'source_ids' => [$source->id],
            'source_from' => now()->subDay(),
            'source_to' => now(),
            'scan_keywords' => ['ЭТрН'],
            'title' => 'Рабочее название',
            'editor_brief' => 'Редакторский бриф',
            'planned_at' => now()->addDay(),
            'status' => CommunityAiScenario::STATUS_REVIEW,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
            'last_error' => 'Старая ошибка',
            'settings' => [
                'custom_setting' => 'keep',
                'editor_persona_id' => $persona->id,
                'source_message_count' => 12,
                'keyword_match_count' => 4,
            ],
        ]);
        $message = $source->messages()->create([
            'external_message_id' => 'clear-scenario-source-message',
            'sender_key' => hash('sha256', 'sender'),
            'text' => 'Сообщение источника должно сохраниться.',
            'content_hash' => hash('sha256', 'source message'),
            'sent_at' => now()->subHour(),
        ]);
        $step = $scenario->steps()->create([
            'community_ai_persona_id' => $persona->id,
            'type' => 'topic',
            'sequence' => 1,
            'planned_delay_minutes' => 0,
            'draft_title' => 'Черновик темы',
            'draft_body' => 'Текст черновика',
            'status' => 'pending_review',
            'idempotency_key' => 'scenario-clear-topic',
        ]);
        CommunityAiGeneration::query()->create([
            'community_ai_scenario_id' => $scenario->id,
            'community_ai_scenario_step_id' => $step->id,
            'community_ai_persona_id' => $persona->id,
            'purpose' => 'topic',
            'status' => 'succeeded',
        ]);

        $this->actingAs(User::factory()->create());

        Livewire::test(EditCommunityAiScenario::class, ['record' => $scenario->getRouteKey()])
            ->callAction('clearResults')
            ->assertHasNoActionErrors()
            ->assertNotified();

        $scenario->refresh();
        $this->assertSame(CommunityAiScenario::STATUS_DRAFT, $scenario->status);
        $this->assertSame('Рабочее название', $scenario->title);
        $this->assertSame('Редакторский бриф', $scenario->editor_brief);
        $this->assertSame(['ЭТрН'], $scenario->scan_keywords);
        $this->assertSame('keep', $scenario->settings['custom_setting']);
        $this->assertArrayNotHasKey('editor_persona_id', $scenario->settings);
        $this->assertNull($scenario->started_at);
        $this->assertNull($scenario->completed_at);
        $this->assertNull($scenario->last_error);
        $this->assertDatabaseMissing('community_ai_scenario_steps', ['id' => $step->id]);
        $this->assertDatabaseCount('community_ai_generations', 0);
        $this->assertDatabaseHas('community_ai_source_messages', ['id' => $message->id]);
    }

    public function test_admin_can_delete_scenario_and_its_generated_data(): void
    {
        $source = CommunityAiSource::query()->firstOrFail();
        $persona = CommunityAiPersona::query()->firstOrFail();
        $scenario = CommunityAiScenario::query()->create([
            'source_ids' => [$source->id],
            'source_from' => now()->subDay(),
            'source_to' => now(),
            'status' => CommunityAiScenario::STATUS_REVIEW,
        ]);
        $step = $scenario->steps()->create([
            'community_ai_persona_id' => $persona->id,
            'type' => 'topic',
            'sequence' => 1,
            'planned_delay_minutes' => 0,
            'draft_title' => 'Черновик темы',
            'draft_body' => 'Текст черновика',
            'status' => 'pending_review',
            'idempotency_key' => 'scenario-delete-topic',
        ]);
        $generation = CommunityAiGeneration::query()->create([
            'community_ai_scenario_id' => $scenario->id,
            'community_ai_scenario_step_id' => $step->id,
            'community_ai_persona_id' => $persona->id,
            'purpose' => 'topic',
            'status' => 'succeeded',
        ]);

        $this->actingAs(User::factory()->create());

        Livewire::test(EditCommunityAiScenario::class, ['record' => $scenario->getRouteKey()])
            ->callAction('delete')
            ->assertHasNoActionErrors()
            ->assertRedirect(CommunityAiScenarioResource::getUrl('index'));

        $this->assertDatabaseMissing('community_ai_scenarios', ['id' => $scenario->id]);
        $this->assertDatabaseMissing('community_ai_scenario_steps', ['id' => $step->id]);
        $this->assertDatabaseMissing('community_ai_generations', ['id' => $generation->id]);
    }

    public function test_it_stops_before_generation_when_keywords_do_not_match_messages(): void
    {
        $source = CommunityAiSource::query()->firstOrFail();
        $source->update(['settings' => ['collection_mode' => 'bot_api']]);
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
        $personas = CommunityAiPersona::query()->take(3)->get();
        $topicDate = now()->subMonths(2)->setTime(9, 15)->setMicrosecond(0);
        $commentDate = $topicDate->copy()->addMinutes(17);
        $replyDate = $commentDate->copy()->addMinutes(9);
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
            'scheduled_at' => $topicDate,
            'status' => 'scheduled',
            'idempotency_key' => 'scenario-test-topic',
        ]);
        $comment = $scenario->steps()->create([
            'community_ai_persona_id' => $personas[1]->id,
            'type' => 'comment',
            'sequence' => 2,
            'planned_delay_minutes' => 10,
            'draft_body' => 'Scenario comment body',
            'scheduled_at' => $commentDate,
            'status' => 'scheduled',
            'idempotency_key' => 'scenario-test-comment',
        ]);
        $reply = $scenario->steps()->create([
            'community_ai_persona_id' => $personas[2]->id,
            'parent_step_id' => $comment->id,
            'type' => 'comment',
            'sequence' => 3,
            'planned_delay_minutes' => 19,
            'draft_body' => 'Scenario nested reply body',
            'scheduled_at' => $replyDate,
            'status' => 'scheduled',
            'idempotency_key' => 'scenario-test-nested-reply',
        ]);

        app()->call([new PublishCommunityAiScenarioStep($topic->id), 'handle']);
        app()->call([new PublishCommunityAiScenarioStep($topic->id), 'handle']);
        app()->call([new PublishCommunityAiScenarioStep($comment->id), 'handle']);
        app()->call([new PublishCommunityAiScenarioStep($reply->id), 'handle']);

        $this->assertDatabaseCount('community_posts', 1);
        $this->assertDatabaseCount('community_comments', 2);
        $publishedPost = CommunityPost::query()->firstOrFail();
        $publishedComment = CommunityComment::query()->where('body_markdown', 'Scenario comment body')->firstOrFail();
        $publishedReply = CommunityComment::query()->where('body_markdown', 'Scenario nested reply body')->firstOrFail();
        $this->assertSame(2, $publishedPost->comments_count);
        $this->assertSame($topicDate->toDateTimeString(), $publishedPost->published_at->toDateTimeString());
        $this->assertSame($topicDate->toDateTimeString(), $publishedPost->created_at->toDateTimeString());
        $this->assertSame($commentDate->toDateTimeString(), $publishedComment->created_at->toDateTimeString());
        $this->assertSame($replyDate->toDateTimeString(), $publishedReply->created_at->toDateTimeString());
        $this->assertSame($publishedComment->id, $publishedReply->parent_id);
        $this->assertSame($publishedComment->id, $publishedReply->root_id);
        $this->assertSame(1, $publishedReply->depth);
        $this->assertSame($topicDate->toDateTimeString(), $topic->fresh()->published_at->toDateTimeString());
        $this->assertSame($commentDate->toDateTimeString(), $comment->fresh()->published_at->toDateTimeString());
        $this->assertSame($replyDate->toDateTimeString(), $reply->fresh()->published_at->toDateTimeString());
        $this->assertSame(CommunityAiScenario::STATUS_COMPLETED, $scenario->fresh()->status);
    }
}
