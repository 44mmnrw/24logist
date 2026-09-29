<?php

namespace Tests\Feature;

use App\Filament\Clusters\Landing\Resources\LandingSections\Pages\EditLandingSection;
use App\Filament\Clusters\Landing\Resources\LandingSections\RelationManagers\BlocksRelationManager;
use App\Models\LandingBlock;
use App\Models\LandingSection;
use App\Models\User;
use App\Services\LandingPageService;
use Database\Seeders\LandingContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class LandingReviewsSectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LandingContentSeeder::class);
        app(LandingPageService::class)->clearCache();
    }

    public function test_reviews_are_hidden_when_section_is_disabled_empty_or_has_no_approved_review(): void
    {
        $section = LandingSection::query()->where('slug', 'reviews')->firstOrFail();

        $this->assertFalse($section->is_active);
        $this->get('/')->assertOk()->assertDontSee('id="reviews"', false);

        $section->update(['is_active' => true]);
        app(LandingPageService::class)->clearCache();
        $this->get('/')->assertOk()->assertDontSee('id="reviews"', false);

        DB::table('landing_blocks')->insert([
            'section_slug' => 'reviews',
            'block_type' => 'review',
            'title' => 'ООО «Несогласованный отзыв»',
            'description' => 'Этот текст не должен попасть на лендинг.',
            'extra' => json_encode([
                'publication_approved' => false,
                'metrics' => [['value' => '10%', 'label' => 'результат']],
            ], JSON_UNESCAPED_UNICODE),
            'is_active' => true,
            'is_highlighted' => false,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        app(LandingPageService::class)->clearCache();

        $this->get('/')
            ->assertOk()
            ->assertDontSee('id="reviews"', false)
            ->assertDontSee('Несогласованный отзыв');
    }

    public function test_approved_review_renders_semantic_card_monogram_and_two_results_before_pricing(): void
    {
        LandingSection::query()->where('slug', 'reviews')->update(['is_active' => true]);
        LandingBlock::query()->create([
            'section_slug' => 'reviews',
            'block_type' => 'review',
            'title' => 'ИП Александра Дальняя Логистическая Компания',
            'description' => 'Перевели заявки и документы в единый рабочий процесс.',
            'extra' => [
                'client_type' => 'individual_entrepreneur',
                'segment' => 'carrier',
                'region' => 'Самарская область',
                'representative_name' => 'Александра Дальняя',
                'representative_position' => 'Руководитель',
                'metrics' => [
                    ['value' => '−40%', 'label' => 'времени на документы'],
                    ['value' => '2 часа', 'label' => 'на запуск нового сотрудника'],
                ],
                'publication_approved' => true,
                'publication_approved_at' => now()->toIso8601String(),
                'publication_approved_by' => 1,
            ],
            'is_active' => true,
            'sort_order' => 1,
        ]);
        app(LandingPageService::class)->clearCache();

        $response = $this->get('/')->assertOk();
        $html = $response->getContent();

        $response
            ->assertSee('id="reviews"', false)
            ->assertSee('<article class="review-card" role="listitem">', false)
            ->assertSee('<blockquote class="review-card__quote">', false)
            ->assertSee('АД')
            ->assertSee('ИП Александра Дальняя Логистическая Компания')
            ->assertSee('Александра Дальняя')
            ->assertSee('−40%')
            ->assertSee('2 часа')
            ->assertSee('Отзыв согласован с клиентом');

        $this->assertLessThan(strpos($html, 'id="pricing"'), strpos($html, 'id="reviews"'));
    }

    public function test_admin_cannot_publish_unapproved_review_and_records_approval_audit_fields(): void
    {
        $section = LandingSection::query()->where('slug', 'reviews')->firstOrFail();
        $admin = User::factory()->create();
        $this->actingAs($admin);
        $review = LandingBlock::query()->create([
            'section_slug' => 'reviews',
            'block_type' => 'review',
            'title' => 'ООО «Проверяемый клиент»',
            'description' => 'Сократили ручную работу с заявками и документами.',
            'extra' => [
                'logo_path' => null,
                'client_type' => 'legal_entity',
                'segment' => 'forwarder',
                'region' => 'Москва',
                'representative_name' => 'Иван Петров',
                'representative_position' => 'Директор по логистике',
                'metrics' => [['value' => '−30%', 'label' => 'ручных операций']],
                'publication_approved' => false,
            ],
            'is_active' => false,
            'sort_order' => 1,
        ]);

        $unapproved = $this->reviewFormData(false, true, 'ООО «Проверяемый клиент»');

        Livewire::test(BlocksRelationManager::class, [
            'ownerRecord' => $section,
            'pageClass' => EditLandingSection::class,
        ])
            ->callTableAction('edit', $review, $unapproved)
            ->assertHasActionErrors(['extra.publication_approved']);

        $this->assertFalse($review->fresh()->is_active);

        Livewire::test(BlocksRelationManager::class, [
            'ownerRecord' => $section,
            'pageClass' => EditLandingSection::class,
        ])
            ->callTableAction('edit', $review, $this->reviewFormData(true, true, 'ООО «Проверяемый клиент»'))
            ->assertHasNoActionErrors();

        $review->refresh();
        $this->assertTrue($review->is_active);
        $this->assertTrue($review->extra['publication_approved']);
        $this->assertNotEmpty($review->extra['publication_approved_at']);
        $this->assertSame($admin->id, $review->extra['publication_approved_by']);
    }

    public function test_admin_dragging_reviews_preserves_their_order(): void
    {
        $section = LandingSection::query()->where('slug', 'reviews')->firstOrFail();
        $first = $this->createApprovedReview('ООО «Первый»', 1);
        $second = $this->createApprovedReview('ООО «Второй»', 2);
        $this->actingAs(User::factory()->create());

        Livewire::test(BlocksRelationManager::class, [
            'ownerRecord' => $section,
            'pageClass' => EditLandingSection::class,
        ])
            ->call('toggleTableReordering')
            ->call('reorderTable', [$second->id, $first->id]);

        $this->assertSame(
            [$second->id, $first->id],
            LandingBlock::query()
                ->where('section_slug', 'reviews')
                ->where('block_type', 'review')
                ->orderBy('sort_order')
                ->pluck('id')
                ->all(),
        );
    }

    public function test_admin_can_create_an_approved_review(): void
    {
        $section = LandingSection::query()->where('slug', 'reviews')->firstOrFail();
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $component = Livewire::test(BlocksRelationManager::class, [
            'ownerRecord' => $section,
            'pageClass' => EditLandingSection::class,
        ])->mountTableAction('create');

        $metricKey = array_key_first($component->get('mountedActions.0.data.review_metrics'));
        $data = $this->reviewFormData(true, true, 'ООО «Новый клиент»');
        unset($data['review_metrics']);

        $component
            ->fillForm($data)
            ->set("mountedActions.0.data.review_metrics.{$metricKey}.value", '−30%')
            ->set("mountedActions.0.data.review_metrics.{$metricKey}.label", 'ручных операций')
            ->callMountedTableAction()
            ->assertHasNoActionErrors();

        $review = LandingBlock::query()->where('title', 'ООО «Новый клиент»')->firstOrFail();
        $this->assertSame('review', $review->block_type);
        $this->assertSame('−30%', $review->extra['metrics'][0]['value']);
        $this->assertSame($admin->id, $review->extra['publication_approved_by']);
    }

    /** @return array<string, mixed> */
    private function reviewFormData(bool $approved, bool $active, string $company): array
    {
        return [
            'title' => $company,
            'description' => 'Сократили ручную работу с заявками и документами.',
            'extra' => [
                'logo_path' => null,
                'client_type' => 'legal_entity',
                'segment' => 'forwarder',
                'region' => 'Москва',
                'representative_name' => 'Иван Петров',
                'representative_position' => 'Директор по логистике',
                'publication_approved' => $approved,
            ],
            'review_metrics' => [
                (string) Str::uuid() => ['value' => '−30%', 'label' => 'ручных операций'],
            ],
            'is_active' => $active,
            'sort_order' => 1,
        ];
    }

    private function createApprovedReview(string $company, int $sortOrder): LandingBlock
    {
        return LandingBlock::query()->create([
            'section_slug' => 'reviews',
            'block_type' => 'review',
            'title' => $company,
            'description' => 'Отзыв клиента.',
            'extra' => [
                'client_type' => 'legal_entity',
                'segment' => 'other',
                'representative_name' => 'Представитель',
                'representative_position' => 'Руководитель',
                'metrics' => [['value' => '1 день', 'label' => 'на внедрение']],
                'publication_approved' => true,
                'publication_approved_at' => now()->toIso8601String(),
                'publication_approved_by' => 1,
            ],
            'is_active' => true,
            'sort_order' => $sortOrder,
        ]);
    }
}
