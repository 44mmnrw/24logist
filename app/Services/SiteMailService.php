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

    public function apply(): bool
    {
        $site = $this->mailSettings();

        if (! filled($site->mail_host)) {
            return false;
        }

        $host = MailHost::normalize((string) $site->mail_host);

        if ($host === null) {
            return false;
        }

        $password = $this->resolvePassword($site);

        $smtpConfig = [
            'transport' => 'smtp',
            'scheme' => $this->mapEncryptionToScheme((string) ($site->mail_encryption ?? 'ssl')),
            'host' => $host,
            'port' => (int) ($site->mail_port ?: 465),
            'username' => filled($site->mail_username) ? (string) $site->mail_username : null,
            'password' => $password,
            'timeout' => 30,
        ];

        if (! ($site->mail_verify_ssl ?? true)) {
            $smtpConfig['stream'] = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];
        }

        config([
            'mail.default' => 'smtp',
            'mail.from.address' => $this->fromAddress($site),
            'mail.from.name' => $this->fromName($site),
            'mail.mailers.smtp' => array_merge(
                (array) config('mail.mailers.smtp', []),
                $smtpConfig,
            ),
        ]);

        $mailManager = app('mail.manager');

        if (method_exists($mailManager, 'forgetMailers')) {
            $mailManager->forgetMailers();
        } elseif (method_exists($mailManager, 'forgetDrivers')) {
            $mailManager->forgetDrivers();
        }

        return true;
    }

    public function sendTest(string $recipient): void
    {
        $site = $this->mailSettings();

        if (! filled($site->mail_host)) {
            throw new \RuntimeException('Укажите SMTP-сервер в настройках почты.');
        }

        if (! $site->hasMailPassword()) {
            throw new \RuntimeException(
                'Пароль SMTP не сохранён в базе. Введите пароль, нажмите «Сохранить» внизу страницы, дождитесь уведомления «Пароль почты сохранён».'
            );
        }

        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Укажите корректный email для теста.');
        }

        if (! $this->apply()) {
            throw new \RuntimeException('Не удалось применить настройки SMTP.');
        }

        try {
            Mail::to($recipient)->send(new SiteMailTestMessage());
        } catch (\Throwable $exception) {
            throw new \RuntimeException($this->humanizeMailError($exception, $site), 0, $exception);
        }
    }

    public function isConfigured(): bool
    {
        $site = $this->mailSettings();

        return filled($site->mail_host)
            && $site->hasMailPassword()
            && filled($this->fromAddress($site));
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
        $message = trim($exception->getMessage());
        $host = MailHost::normalize((string) $site->mail_host) ?? (string) $site->mail_host;

        if (str_contains($message, 'Authentication failed') || str_contains($message, '535') || str_contains($message, '534')) {
            return 'Ошибка авторизации SMTP: проверьте логин (полный email) и пароль почтового ящика.';
        }

        if (str_contains($message, 'Connection could not be established') || str_contains($message, 'Connection timed out')) {
            return 'Не удалось подключиться к '.$host.':'.$site->mail_port.'. Проверьте хост, порт и тип шифрования.';
        }

        if (str_contains($message, 'certificate verify failed') || str_contains($message, 'SSL operation failed')) {
            return $message.' (SMTP: '.$host.':'.$site->mail_port.')';
        }

        return $message;
    }
}
