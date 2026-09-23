<?php

namespace Tests\Feature;

use App\Mail\CommercialOfferDocuments;
use App\Mail\LandingLeadWelcome;
use App\Models\LandingBlock;
use App\Models\LandingLead;
use App\Models\SiteSetting;
use App\Services\CommercialOfferDeliveryService;
use App\Services\CommercialOfferPdfService;
use App\Services\PublicPageCache;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CommercialOfferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        app(PublicPageCache::class)->forgetLanding();
        Mail::fake();
        SiteSetting::instance()->update([
            'mail_host' => 'smtp.example.test',
            'mail_username' => 'mailer@example.test',
            'mail_password' => 'test-password',
            'mail_from_address' => 'mailer@example.test',
            'leads_notifications_enabled' => false,
            'leads_welcome_enabled' => true,
        ]);
        app(SiteSettingsService::class)->clearCache();
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
            'billing_period' => 'year',
            'option_ids' => [$option->id],
            'website' => '',
        ])->assertCreated()
            ->assertJsonPath('documents_sent', true);

        $lead = LandingLead::query()->sole();
        $this->assertSame(LandingLead::TYPE_COMMERCIAL_OFFER, $lead->type);
        $this->assertSame('ivan@example.test', $lead->email);
        $this->assertSame($plan->id, $lead->recommended_plan_id);
        $this->assertSame('ООО Тестовая логистика', $lead->quiz_answers[0]['answer']);
        $this->assertSame('7707083893', $lead->quiz_answers[1]['answer']);
        $this->assertSame('3', $lead->quiz_answers[2]['answer']);
        $this->assertSame('Год', $lead->quiz_answers[3]['answer']);
        $this->assertSame($option->title, $lead->quiz_answers[4]['answer']);
        $this->assertSame('61 200 ₽/год', $lead->quiz_answers[5]['answer']);
        $this->assertSame(61200, $lead->offer_details['total']);
        $this->assertSame(1200, $lead->offer_details['unit_price']);
        $this->assertSame(1500, $lead->offer_details['options'][0]['price']);
        $this->assertNotNull($lead->offer_sent_at);

        Mail::assertSent(CommercialOfferDocuments::class, function (CommercialOfferDocuments $mail): bool {
            $this->assertStringStartsWith('%PDF-', $mail->offerPdf);
            $this->assertCount(2, $mail->attachments());

            // Exercise actual MIME attachment creation with a local array transport.
            $manager = new MailManager($this->app);
            $message = $mail->send($manager->mailer('array'))->getSymfonySentMessage()->getOriginalMessage();
            $attachments = collect($message->getAttachments())->keyBy(fn ($attachment): string => $attachment->getFilename());
            $this->assertCount(2, $attachments);
            $this->assertSame($mail->offerPdf, $attachments['логистРу — Коммерческое предложение.pdf']->getBody());
            $this->assertSame(file_get_contents(resource_path('documents/functional-characteristics.pdf')), $attachments['логистРу — Функциональные характеристики.pdf']->getBody());

            return $mail->hasTo('ivan@example.test');
        });
        Mail::assertNotSent(LandingLeadWelcome::class);
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
        Mail::assertNothingSent();
        $this->assertDatabaseCount('landing_leads', 0);
    }

    public function test_monthly_offer_uses_server_prices_and_keeps_a_snapshot(): void
    {
        $this->postJson(route('leads.commercial-offer.store'), $this->offerPayload([
            'total' => 1,
            'unit_price' => 1,
            'users' => 2,
            'company' => '<b>Компания & партнёры</b>',
        ]))->assertCreated()->assertJsonPath('documents_sent', true);

        $lead = LandingLead::query()->sole();
        $this->assertSame(2400, $lead->offer_details['total']);
        $this->assertSame([], $lead->offer_details['options']);
        LandingBlock::query()->whereKey($lead->recommended_plan_id)->update(['price' => 99000, 'title' => 'Новый тариф']);
        $html = view('pdf.commercial-offer', ['lead' => $lead->fresh(), 'offer' => $lead->offer_details, 'logo' => '', 'signature' => ''])->render();
        $this->assertStringContainsString('2 400', $html);
        $this->assertStringContainsString('Итого за месяц', $html);
        $this->assertStringContainsString('&lt;b&gt;Компания &amp; партнёры&lt;/b&gt;', $html);
        $this->assertStringNotContainsString('<b>Компания', $html);
        $this->assertStringNotContainsString('Новый тариф', $html);
        $this->assertStringContainsString($lead->name, $html);
        $this->assertStringContainsString($lead->email, $html);
        $this->assertStringContainsString($lead->phone, $html);

        $this->assertTrue(app(CommercialOfferDeliveryService::class)->send($lead->fresh()));
        Mail::assertSent(CommercialOfferDocuments::class, 1);
    }

    public function test_documents_are_sent_even_if_generic_welcome_email_is_disabled(): void
    {
        SiteSetting::instance()->update(['leads_welcome_enabled' => false]);
        app(SiteSettingsService::class)->clearCache();
        $this->postJson(route('leads.commercial-offer.store'), $this->offerPayload())
            ->assertCreated()->assertJsonPath('documents_sent', true);
        Mail::assertSent(CommercialOfferDocuments::class, 1);
    }

    public function test_mail_configuration_failure_keeps_lead_and_does_not_claim_delivery(): void
    {
        SiteSetting::instance()->update(['mail_host' => null, 'mail_password' => null]);
        app(SiteSettingsService::class)->clearCache();
        $this->postJson(route('leads.commercial-offer.store'), $this->offerPayload())
            ->assertCreated()->assertJsonPath('documents_sent', false)
            ->assertJsonPath('message', 'Заявка сохранена, но отправить документы автоматически не удалось. Мы свяжемся с вами.');
        $this->assertNull(LandingLead::query()->sole()->offer_sent_at);
        Mail::assertNothingSent();
    }

    public function test_pdf_failure_does_not_send_an_incomplete_email(): void
    {
        $this->mock(CommercialOfferPdfService::class)->shouldReceive('render')
            ->once()->andThrow(new \RuntimeException('PDF unavailable'));
        $this->postJson(route('leads.commercial-offer.store'), $this->offerPayload())
            ->assertCreated()->assertJsonPath('documents_sent', false);
        $this->assertNull(LandingLead::query()->sole()->offer_sent_at);
        Mail::assertNothingSent();
    }

    public function test_smtp_failure_does_not_mark_documents_as_sent(): void
    {
        Mail::shouldReceive('forgetMailers')->once();
        Mail::shouldReceive('to')->once()->with('ivan@example.test')->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('SMTP unavailable'));
        $this->postJson(route('leads.commercial-offer.store'), $this->offerPayload())
            ->assertCreated()->assertJsonPath('documents_sent', false);
        $this->assertNull(LandingLead::query()->sole()->offer_sent_at);
    }

    public function test_unavailable_plan_or_options_do_not_produce_an_incorrect_offer(): void
    {
        $this->postJson(route('leads.commercial-offer.store'), $this->offerPayload(['option_ids' => [999999]]))
            ->assertUnprocessable()->assertJsonValidationErrors('option_ids');
        LandingBlock::query()->where('section_slug', 'pricing_wide')->update(['is_active' => false]);
        $this->postJson(route('leads.commercial-offer.store'), $this->offerPayload())
            ->assertUnprocessable()->assertJsonValidationErrors('users');
        $this->assertDatabaseCount('landing_leads', 0);
        Mail::assertNothingSent();
    }

    /** @return array<string, mixed> */
    private function offerPayload(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Иван Петров',
            'company' => 'ООО Тестовая логистика',
            'inn' => '7707083893',
            'email' => 'ivan@example.test',
            'phone' => '+7 (900) 000-00-00',
            'privacy_accepted' => true,
            'users' => 3,
            'billing_period' => 'month',
            'option_ids' => [],
        ], $overrides);
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
