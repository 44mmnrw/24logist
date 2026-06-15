<?php

namespace Tests\Feature;

use App\Mail\SiteMailTestMessage;
use App\Models\SiteSetting;
use App\Services\SiteMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SiteMailServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_apply_configures_site_smtp_mailer(): void
    {
        SiteSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'mail_host' => 'smtp.example.com',
                'mail_port' => 587,
                'mail_encryption' => 'smtp',
                'mail_username' => 'mailer@example.com',
                'mail_password' => 'secret',
                'mail_from_address' => 'mailer@example.com',
                'mail_from_name' => 'ЛогистРу',
            ],
        );

        app(\App\Services\SiteSettingsService::class)->clearCache();

        $applied = app(SiteMailService::class)->apply();

        $this->assertTrue($applied);
        $this->assertSame('site_smtp', config('mail.default'));
        $this->assertSame('smtp.example.com', config('mail.mailers.site_smtp.host'));
        $this->assertSame('mailer@example.com', config('mail.from.address'));
    }

    public function test_send_test_uses_configured_mailer(): void
    {
        Mail::fake();

        SiteSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'mail_host' => 'smtp.example.com',
                'mail_port' => 465,
                'mail_encryption' => 'smtps',
                'mail_from_address' => 'info@24logist.ru',
                'mail_from_name' => 'ЛогистРу',
            ],
        );

        app(\App\Services\SiteSettingsService::class)->clearCache();

        app(SiteMailService::class)->sendTest('admin@example.com');

        Mail::assertSent(SiteMailTestMessage::class);
    }
}
