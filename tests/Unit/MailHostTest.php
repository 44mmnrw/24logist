<?php

namespace Tests\Unit;

use App\Support\MailHost;
use Tests\TestCase;

class MailHostTest extends TestCase
{
    public function test_normalizes_ssl_prefix_and_path(): void
    {
        $this->assertSame('smtp.yandex.ru', MailHost::normalize('ssl://smtp.yandex.ru:465'));
        $this->assertSame('mail.24logist.ru', MailHost::normalize('mail.24logist.ru/'));
    }

    public function test_detects_website_host_mistake(): void
    {
        config(['app.url' => 'https://24logist.ru']);

        $this->assertTrue(MailHost::looksLikeWebsiteHost('24logist.ru'));
        $this->assertFalse(MailHost::looksLikeWebsiteHost('smtp.yandex.ru'));
        $this->assertFalse(MailHost::looksLikeWebsiteHost('mail.24logist.ru'));
    }
}
