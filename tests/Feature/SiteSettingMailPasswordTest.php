<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteSettingMailPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_mail_password_is_encrypted_in_database(): void
    {
        $setting = SiteSetting::instance();
        $setting->mail_password = 'mailbox-secret';
        $setting->save();

        $raw = SiteSetting::query()->where('id', 1)->value('mail_password');

        $this->assertNotNull($raw);
        $this->assertTrue($setting->fresh()->hasMailPassword());
        $this->assertSame('mailbox-secret', $setting->fresh()->mail_password);
    }
}
