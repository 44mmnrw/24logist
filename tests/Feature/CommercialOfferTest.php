<?php

namespace Tests\Feature;

use App\Models\LandingBlock;
use App\Models\LandingLead;
use App\Models\SiteSetting;
use App\Services\PublicPageCache;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CommercialOfferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        app(PublicPageCache::class)->forgetLanding();
    }

    public function test_offer_button_opens_a_required_form_with_dadata_suggestions(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('data-commercial-offer-open', false)
            ->assertSee('data-commercial-offer-modal', false)
            ->assertSee(route('leads.commercial-offer.store'), false)
            ->assertSee(route('leads.party-suggestions'), false)
            ->assertSee('name="name"', false)
            ->assertSee('name="company"', false)
            ->assertSee('name="inn"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="phone"', false)
            ->assertSee('name="privacy_accepted"', false)
            ->assertSee('data-commercial-offer-consent', false)
            ->assertSee('data-default-text="Получить предложение" disabled', false)
            ->assertSee('class="commercial-offer-modal__submit-hint"', false)
            ->assertSee('Чтобы получить предложение, согласитесь с политикой конфиденциальности')
            ->assertSee('data-party-account-name', false)
            ->assertSee('data-party-inn', false);
    }

    public function test_commercial_offer_is_stored_with_server_calculated_configuration(): void
    {
        $plan = LandingBlock::query()
            ->where('section_slug', 'pricing_wide')
            ->where('block_type', 'plan')
            ->firstOrFail();
        $option = $plan->children()->where('block_type', 'paid_option')->firstOrFail();

        $this->postJson(route('leads.commercial-offer.store'), [
            'name' => 'Иван Петров',
            'company' => 'ООО Тестовая логистика',
            'inn' => '7707083893',
            'email' => 'ivan@example.test',
            'phone' => '+7 (900) 000-00-00',
            'privacy_accepted' => true,
            'users' => 3,
            'option_ids' => [$option->id],
            'website' => '',
        ])->assertCreated()
            ->assertJsonPath('message', 'Заявка принята. Мы подготовим коммерческое предложение и свяжемся с вами.');

        $lead = LandingLead::query()->sole();
        $this->assertSame(LandingLead::TYPE_COMMERCIAL_OFFER, $lead->type);
        $this->assertSame('ivan@example.test', $lead->email);
        $this->assertSame($plan->id, $lead->recommended_plan_id);
        $this->assertSame('ООО Тестовая логистика', $lead->quiz_answers[0]['answer']);
        $this->assertSame('7707083893', $lead->quiz_answers[1]['answer']);
        $this->assertSame('3', $lead->quiz_answers[2]['answer']);
        $this->assertSame($option->title, $lead->quiz_answers[3]['answer']);
        $this->assertSame('5 100 ₽/мес', $lead->quiz_answers[4]['answer']);
    }

    public function test_all_contact_fields_are_required_for_commercial_offer(): void
    {
        $this->postJson(route('leads.commercial-offer.store'), [
            'name' => '',
            'company' => '',
            'inn' => '',
            'email' => '',
            'phone' => '',
            'privacy_accepted' => false,
            'users' => 1,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'company', 'inn', 'email', 'phone', 'privacy_accepted']);
    }

    public function test_commercial_offer_party_suggestions_work_when_cabinet_registration_is_disabled(): void
    {
        SiteSetting::instance()->update([
            'cabinet_registration_enabled' => false,
            'cabinet_login_url' => 'https://platform.example.test',
            'cabinet_login_origin' => 'https://landing.example.test',
            'cabinet_login_connect_ip' => '127.0.0.1',
            'cabinet_login_api_secret' => str_repeat('s', 32),
            'cabinet_login_api_timeout' => 12,
        ]);
        app(SiteSettingsService::class)->clearCache();

        Http::fake([
            'platform.example.test/*' => Http::response([
                'suggestions' => [[
                    'inn' => '7707083893',
                    'account_name' => 'ПАО СБЕРБАНК',
                ]],
            ]),
        ]);

        $this->postJson(route('leads.party-suggestions'), ['query' => 'Сбер'])
            ->assertOk()
            ->assertExactJson(['suggestions' => [[
                'inn' => '7707083893',
                'account_name' => 'ПАО СБЕРБАНК',
            ]]])
            ->assertHeader('Cache-Control', 'no-store, private');

        Http::assertSentCount(1);
    }
}
