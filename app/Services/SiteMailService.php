<?php

namespace App\Services;

use App\Mail\SiteMailTestMessage;
use App\Models\SiteSetting;
use App\Support\MailHost;
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

        $host = MailHost::normalize((string) $site->mail_host);

        if ($host === null) {
            return false;
        }

        $mailerConfig = [
            'transport' => 'smtp',
            'scheme' => filled($site->mail_encryption) ? (string) $site->mail_encryption : null,
            'host' => $host,
            'port' => (int) ($site->mail_port ?: 465),
            'username' => filled($site->mail_username) ? (string) $site->mail_username : null,
            'password' => filled($site->mail_password) ? (string) $site->mail_password : null,
            'timeout' => null,
            'local_domain' => $host,
        ];

        if (! ($site->mail_verify_ssl ?? true)) {
            $mailerConfig['stream'] = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];
        }

        config([
            'mail.default' => 'site_smtp',
            'mail.mailers.site_smtp' => $mailerConfig,
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

        try {
            Mail::to($recipient)->send(new SiteMailTestMessage());
        } catch (\Throwable $exception) {
            throw new \RuntimeException($this->humanizeMailError($exception, $site), 0, $exception);
        }
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

    private function humanizeMailError(\Throwable $exception, SiteSetting $site): string
    {
        $message = $exception->getMessage();
        $host = MailHost::normalize((string) $site->mail_host) ?? (string) $site->mail_host;

        if (str_contains($message, 'certificate verify failed') || str_contains($message, 'SSL operation failed')) {
            return 'Ошибка SSL при подключении к '.$host.'. '
                .'На многих серверах с почтой на том же домене (например 24logist.ru) стоит самоподписанный сертификат — '
                .'отключите «Проверять SSL-сертификат SMTP», сохраните настройки и повторите тест.';
        }

        if (str_contains($message, 'Authentication failed') || str_contains($message, '535')) {
            return 'Ошибка авторизации SMTP: проверьте логин и пароль. Для Яндекса нужен пароль приложения, не пароль от аккаунта.';
        }

        if (str_contains($message, 'Connection could not be established')) {
            return 'Не удалось подключиться к '.$host.'. Проверьте хост, порт и шифрование (465 + SSL или 587 + STARTTLS).';
        }

        return $message;
    }
}
