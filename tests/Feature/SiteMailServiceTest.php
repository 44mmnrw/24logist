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

    public function test_apply_configures_smtp_mailer_like_platform_service(): void
    {
        SiteSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'mail_host' => 'smtp.example.com',
                'mail_port' => 587,
                'mail_encryption' => 'tls',
                'mail_username' => 'mailer@example.com',
                'mail_password' => 'secret',
                'mail_from_address' => 'mailer@example.com',
                'mail_from_name' => 'ЛогистРу',
            ],
        );

        app(\App\Services\SiteSettingsService::class)->clearCache();

        $applied = app(SiteMailService::class)->apply();

        $this->assertTrue($applied);
        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.example.com', config('mail.mailers.smtp.host'));
        $this->assertSame('smtp', config('mail.mailers.smtp.scheme'));
        $this->assertSame('mailer@example.com', config('mail.from.address'));
        $this->assertNull(config('mail.mailers.smtp.stream'));
    }

    public function test_apply_maps_legacy_smtps_encryption_value(): void
    {
        SiteSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'mail_host' => '24logist.ru',
                'mail_port' => 465,
                'mail_encryption' => 'smtps',
                'mail_password' => 'secret',
            ],
        );

        app(\App\Services\SiteSettingsService::class)->clearCache();

        app(SiteMailService::class)->apply();

        $this->assertSame('smtps', config('mail.mailers.smtp.scheme'));
    }

    public function test_send_test_uses_configured_mailer(): void
    {
        Mail::fake();

        SiteSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'mail_host' => 'smtp.example.com',
                'mail_port' => 465,
                'mail_encryption' => 'ssl',
                'mail_password' => 'secret',
                'mail_from_address' => 'info@24logist.ru',
                'mail_from_name' => 'ЛогистРу',
            ],
        );

        app(\App\Services\SiteSettingsService::class)->clearCache();

        app(SiteMailService::class)->sendTest('admin@example.com');

        Mail::assertSent(SiteMailTestMessage::class);
    }

    public function test_apply_disables_ssl_verification_when_configured(): void
    {
        SiteSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'mail_host' => 'mail.example.com',
                'mail_port' => 465,
                'mail_encryption' => 'ssl',
                'mail_verify_ssl' => false,
            ],
        );

        app(\App\Services\SiteSettingsService::class)->clearCache();

        app(SiteMailService::class)->apply();

        $this->assertFalse(config('mail.mailers.smtp.stream.ssl.verify_peer'));
    }

    public function test_send_test_requires_saved_password(): void
    {
        SiteSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'mail_host' => 'smtp.example.com',
                'mail_port' => 465,
                'mail_encryption' => 'ssl',
                'mail_from_address' => 'info@24logist.ru',
            ],
        );

        app(\App\Services\SiteSettingsService::class)->clearCache();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Пароль SMTP не сохранён');

        app(SiteMailService::class)->sendTest('admin@example.com');
    }

    public function test_send_test_shows_real_error_on_failure(): void
    {
        SiteSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'mail_host' => '24logist.ru',
                'mail_port' => 465,
                'mail_encryption' => 'ssl',
                'mail_password' => 'secret',
                'mail_verify_ssl' => true,
                'mail_from_address' => 'info@24logist.ru',
            ],
        );

        app(\App\Services\SiteSettingsService::class)->clearCache();

        $this->expectException(\RuntimeException::class);

        try {
            app(SiteMailService::class)->sendTest('admin@example.com');
        } catch (\RuntimeException $exception) {
            $this->assertStringNotContainsString('Undefined variable', $exception->getMessage());
            $this->assertStringNotContainsString('самоподписанный', $exception->getMessage());

            throw $exception;
        }
    }
}
