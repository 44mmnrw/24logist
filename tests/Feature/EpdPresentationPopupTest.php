<?php

namespace Tests\Feature;

use App\Mail\LandingLeadReceived;
use App\Models\LandingLead;
use App\Models\SiteSetting;
use App\Services\PublicPageCache;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EpdPresentationPopupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        app(PublicPageCache::class)->forgetLanding();
    }

    public function test_popup_is_rendered_on_public_pages(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('data-epd-popup', false)
            ->assertSee('images/epd-announcement-27-08.png', false)
            ->assertSee(route('leads.epd-presentation.store'), false)
            ->assertSee('Оставить заявку');
    }

    public function test_registration_banner_variant_is_rendered_when_selected(): void
    {
        SiteSetting::instance()->update([
            'epd_popup_enabled' => true,
            'epd_popup_registration_enabled' => true,
        ]);
        app(SiteSettingsService::class)->clearCache();

        $this->get('/')
            ->assertOk()
            ->assertSee('data-popup-variant="registration"', false)
            ->assertSee('images/epd-test-access-14-days.png', false)
            ->assertSee('https://logistsystem.ru/register', false)
            ->assertSee('Создать личный кабинет')
            ->assertSee('50% скидка')
            ->assertDontSee('data-epd-form', false)
            ->assertDontSee(route('leads.epd-presentation.store'), false);
    }

    public function test_popup_can_be_disabled_in_site_settings(): void
    {
        SiteSetting::instance()->update(['epd_popup_enabled' => false]);
        app(SiteSettingsService::class)->clearCache();

        $this->get('/')
            ->assertOk()
            ->assertDontSee('data-epd-popup', false)
            ->assertDontSee('images/epd-announcement-27-08.png', false);
    }

    public function test_epd_presentation_lead_is_stored_and_notifies_admins(): void
    {
        Mail::fake();

        SiteSetting::instance()->update([
            'leads_notifications_enabled' => true,
            'leads_notification_emails' => 'leads@example.com',
            'mail_host' => 'smtp.example.com',
            'mail_port' => 465,
            'mail_encryption' => 'ssl',
            'mail_username' => 'mailer@example.com',
            'mail_password' => 'secret',
            'mail_from_address' => 'mailer@example.com',
        ]);
        app(SiteSettingsService::class)->clearCache();

        $response = $this->postJson(route('leads.epd-presentation.store'), [
            'company' => 'ООО Тестовая логистика',
            'inn' => '7707083893',
            'role' => 'carrier',
            'document_system' => '1С и Диадок',
            'contact' => 'Иван Петров',
            'phone' => '+7 900 000-00-00',
            'website' => '',
        ]);

        $response->assertCreated()->assertJsonPath('message', 'Заявка принята. Мы свяжемся с вами для согласования презентации.');

        $lead = LandingLead::query()->sole();

        $this->assertSame(LandingLead::TYPE_EPD_PRESENTATION, $lead->type);
        $this->assertSame('Иван Петров', $lead->name);
        $this->assertSame('ООО Тестовая логистика', $lead->quiz_answers[0]['answer']);
        $this->assertSame('Перевозчик', $lead->quiz_answers[2]['answer']);
        $this->assertSame('1С и Диадок', $lead->quiz_answers[3]['answer']);

        Mail::assertSent(LandingLeadReceived::class, function (LandingLeadReceived $mail): bool {
            return $mail->hasTo('leads@example.com')
                && $mail->lead->type === LandingLead::TYPE_EPD_PRESENTATION
                && str_contains($mail->bodyText, 'ООО Тестовая логистика')
                && str_contains($mail->bodyText, '1С и Диадок');
        });
    }

    public function test_epd_presentation_validates_required_fields_and_inn(): void
    {
        $this->postJson(route('leads.epd-presentation.store'), [
            'company' => 'ООО Тест',
            'inn' => '123',
            'role' => 'unknown',
            'document_system' => '',
            'contact' => '',
            'phone' => '',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['inn', 'role', 'document_system', 'contact', 'phone']);
    }
}
