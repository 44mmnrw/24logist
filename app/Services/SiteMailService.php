<?php

namespace App\Services;

use App\Mail\SiteMailTestMessage;
use App\Models\SiteSetting;
use App\Support\MailHost;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Mail;

final class SiteMailService
{
    public function __construct(
        private readonly SiteSettingsService $settings,
    ) {}

    public function apply(): void
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('SMTP не настроен в разделе «Настройки сайта» → «Почта».');
        }

        $site = $this->mailSettings();
        $host = MailHost::normalize((string) $site->mail_host);

        if ($host === null) {
            throw new \RuntimeException('Укажите корректный SMTP-сервер в настройках почты.');
        }

        $password = $this->resolvePassword($site);
        $scheme = $this->mapEncryptionToScheme((string) ($site->mail_encryption ?? 'ssl'));

        config([
            'mail.default' => 'smtp',
            'mail.from.address' => $this->fromAddress($site),
            'mail.from.name' => $this->fromName($site),
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.scheme' => $scheme,
            'mail.mailers.smtp.url' => null,
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => (int) ($site->mail_port ?: 465),
            'mail.mailers.smtp.username' => filled($site->mail_username) ? (string) $site->mail_username : null,
            'mail.mailers.smtp.password' => $password,
            'mail.mailers.smtp.timeout' => 30,
            'mail.mailers.smtp.stream' => [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ],
        ]);

        $mailManager = app('mail.manager');

        if (method_exists($mailManager, 'forgetMailers')) {
            $mailManager->forgetMailers();
        } elseif (method_exists($mailManager, 'forgetDrivers')) {
            $mailManager->forgetDrivers();
        }
    }

    public function sendTest(string $recipient): void
    {
        $site = $this->mailSettings();

        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Укажите корректный email для теста.');
        }

        $this->apply();

        try {
            Mail::to($recipient)->send(new SiteMailTestMessage());
        } catch (\Throwable $exception) {
            throw new \RuntimeException($this->formatMailError($exception, $site), 0, $exception);
        }
    }

    public function isConfigured(): bool
    {
        $site = $this->mailSettings();

        return filled($site->mail_host)
            && $site->hasMailPassword()
            && filled($site->mail_from_address ?: $site->mail_username);
    }

    private function mailSettings(): SiteSetting
    {
        return SiteSetting::query()->findOrFail(SiteSetting::instance()->getKey());
    }

    private function resolvePassword(SiteSetting $site): ?string
    {
        if (! $site->hasMailPassword()) {
            return null;
        }

        try {
            $password = (string) $site->mail_password;

            return $password !== '' ? $password : null;
        } catch (DecryptException) {
            throw new \RuntimeException(
                'Не удалось прочитать пароль из базы (возможно, менялся APP_KEY на сервере). Введите пароль заново и сохраните настройки.'
            );
        }
    }

    private function mapEncryptionToScheme(string $encryption): ?string
    {
        return match (mb_strtolower(trim($encryption))) {
            'ssl', 'smtps' => 'smtps',
            'tls', 'smtp' => 'smtp',
            default => null,
        };
    }

    private function fromAddress(SiteSetting $site): string
    {
        if (filled($site->mail_from_address)) {
            return (string) $site->mail_from_address;
        }

        if (filled($site->mail_username)) {
            return (string) $site->mail_username;
        }

        throw new \RuntimeException('Укажите адрес отправителя в настройках почты.');
    }

    private function fromName(SiteSetting $site): string
    {
        if (filled($site->mail_from_name)) {
            return (string) $site->mail_from_name;
        }

        return (string) config('app.name', 'ЛогистРу');
    }

    private function formatMailError(\Throwable $exception, SiteSetting $site): string
    {
        $message = trim($exception->getMessage());
        $host = MailHost::normalize((string) $site->mail_host) ?? (string) $site->mail_host;
        $scheme = $this->mapEncryptionToScheme((string) ($site->mail_encryption ?? 'ssl')) ?? 'none';

        return $message.' ['.$scheme.'://'.$host.':'.($site->mail_port ?: 465).']';
    }
}
