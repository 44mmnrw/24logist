<?php

namespace App\Services;

use App\Mail\SiteMailTestMessage;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Mail;

final class SiteMailService
{
    public function __construct(
        private readonly SiteSettingsService $settings,
    ) {}

    public function apply(): bool
    {
        $site = $this->settings->get();

        if (! filled($site->mail_host)) {
            return false;
        }

        config([
            'mail.default' => 'site_smtp',
            'mail.mailers.site_smtp.host' => (string) $site->mail_host,
            'mail.mailers.site_smtp.port' => (int) ($site->mail_port ?: 465),
            'mail.mailers.site_smtp.scheme' => filled($site->mail_encryption) ? (string) $site->mail_encryption : null,
            'mail.mailers.site_smtp.username' => filled($site->mail_username) ? (string) $site->mail_username : null,
            'mail.mailers.site_smtp.password' => filled($site->mail_password) ? (string) $site->mail_password : null,
            'mail.from.address' => $this->fromAddress($site),
            'mail.from.name' => $this->fromName($site),
        ]);

        Mail::purge('site_smtp');

        return true;
    }

    public function sendTest(string $recipient): void
    {
        if (! $this->apply()) {
            throw new \RuntimeException('Укажите SMTP-сервер в настройках почты.');
        }

        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Укажите корректный email для теста.');
        }

        Mail::to($recipient)->send(new SiteMailTestMessage());
    }

    public function isConfigured(): bool
    {
        $site = $this->settings->get();

        return filled($site->mail_host)
            && filled($this->fromAddress($site));
    }

    private function fromAddress(SiteSetting $site): string
    {
        if (filled($site->mail_from_address)) {
            return (string) $site->mail_from_address;
        }

        if (filled($site->mail_username)) {
            return (string) $site->mail_username;
        }

        return (string) config('mail.from.address');
    }

    private function fromName(SiteSetting $site): string
    {
        if (filled($site->mail_from_name)) {
            return (string) $site->mail_from_name;
        }

        return (string) config('mail.from.name', config('app.name'));
    }
}
