<?php

namespace Tests\Feature;

use App\Mail\LandingLeadReceived;
use App\Mail\LandingLeadWelcome;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LandingLeadNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SiteSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'leads_notifications_enabled' => true,
                'leads_notification_emails' => 'leads@example.com',
                'leads_welcome_enabled' => true,
                'leads_welcome_subject' => 'Спасибо, {name}!',
                'leads_welcome_body' => 'Здравствуйте, {name}! Мы получили заявку.',
            ],
        );

        app(\App\Services\SiteSettingsService::class)->clearCache();
    }

    public function test_contact_lead_sends_admin_and_welcome_emails(): void
    {
        Mail::fake();

        $response = $this->postJson(route('leads.contact.store'), [
            'name' => 'Иван Тестов',
            'phone' => '+7 900 000-00-00',
            'email' => 'client@example.com',
            'message' => 'Нужен тариф Стандарт',
        ]);

        $response->assertCreated();

        Mail::assertSent(LandingLeadReceived::class, function (LandingLeadReceived $mail): bool {
            return $mail->hasTo('leads@example.com')
                && $mail->lead->name === 'Иван Тестов';
        });

        Mail::assertSent(LandingLeadWelcome::class, function (LandingLeadWelcome $mail): bool {
            return $mail->hasTo('client@example.com')
                && str_contains($mail->subjectLine, 'Иван Тестов')
                && str_contains($mail->bodyText, 'Иван Тестов');
        });
    }

    public function test_welcome_is_not_sent_without_client_email(): void
    {
        Mail::fake();

        $this->postJson(route('leads.contact.store'), [
            'name' => 'Иван',
            'phone' => '+7 900 000-00-00',
        ])->assertCreated();

        Mail::assertSent(LandingLeadReceived::class);
        Mail::assertNotSent(LandingLeadWelcome::class);
    }

    public function test_admin_notification_is_not_sent_when_disabled(): void
    {
        Mail::fake();

        SiteSetting::query()->where('id', 1)->update(['leads_notifications_enabled' => false]);
        app(\App\Services\SiteSettingsService::class)->clearCache();

        $this->postJson(route('leads.contact.store'), [
            'name' => 'Иван',
            'phone' => '+7 900 000-00-00',
            'email' => 'client@example.com',
        ])->assertCreated();

        Mail::assertNotSent(LandingLeadReceived::class);
        Mail::assertSent(LandingLeadWelcome::class);
    }

    public function test_welcome_is_not_sent_when_disabled(): void
    {
        Mail::fake();

        SiteSetting::query()->where('id', 1)->update(['leads_welcome_enabled' => false]);
        app(\App\Services\SiteSettingsService::class)->clearCache();

        $this->postJson(route('leads.contact.store'), [
            'name' => 'Иван',
            'phone' => '+7 900 000-00-00',
            'email' => 'client@example.com',
        ])->assertCreated();

        Mail::assertSent(LandingLeadReceived::class);
        Mail::assertNotSent(LandingLeadWelcome::class);
    }
}
