<?php

namespace Tests\Unit;

use App\Support\MailHost;
use Tests\TestCase;

class MailHostTest extends TestCase
{
    public function test_normalizes_ssl_prefix_and_path(): void
    {
        $this->assertSame('smtp.yandex.ru', MailHost::normalize('ssl://smtp.yandex.ru:465'));
        $this->assertSame('24logist.ru', MailHost::normalize('24logist.ru'));
        $this->assertSame('mail.24logist.ru', MailHost::normalize('mail.24logist.ru/'));
    }
}
