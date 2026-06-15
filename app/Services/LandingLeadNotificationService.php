<?php

namespace App\Services;

use App\Mail\LandingLeadReceived;
use App\Mail\LandingLeadWelcome;
use App\Models\LandingLead;
use App\Models\SiteSetting;
use App\Support\LandingLeadMailTemplate;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class LandingLeadNotificationService
{
    public function __construct(
        private readonly SiteSettingsService $settings,
        private readonly SiteMailService $mail,
    ) {}

    public function send(LandingLead $lead): void
    {
        $this->sendAdminNotification($lead);
        $this->sendWelcomeEmail($lead);
    }

    public function sendAdminNotification(LandingLead $lead): void
    {
        $site = $this->settings->get();

        if (! $site->leads_notifications_enabled) {
            return;
        }

        $recipients = $this->resolveAdminRecipients($site);

        if ($recipients === []) {
            return;
        }

        try {
            $this->mail->apply();

            Mail::to($recipients)->send(new LandingLeadReceived($lead));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function sendWelcomeEmail(LandingLead $lead): void
    {
        $site = $this->settings->get();

        if (! $site->leads_welcome_enabled) {
            return;
        }

        $recipient = trim((string) ($lead->email ?? ''));

        if ($recipient === '' || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $subjectTemplate = filled($site->leads_welcome_subject)
            ? (string) $site->leads_welcome_subject
            : SiteSetting::defaultLeadsWelcomeSubject();

        $bodyTemplate = filled($site->leads_welcome_body)
            ? (string) $site->leads_welcome_body
            : SiteSetting::defaultLeadsWelcomeBody();

        $subject = LandingLeadMailTemplate::render($subjectTemplate, $lead, $site);
        $body = LandingLeadMailTemplate::render($bodyTemplate, $lead, $site);

        try {
            $this->mail->apply();

            Mail::to($recipient)->send(new LandingLeadWelcome($lead, $subject, $body));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * @return list<string>
     */
    public function resolveAdminRecipients(SiteSetting $site): array
    {
        $emails = $this->parseEmails((string) ($site->leads_notification_emails ?? ''));

        if ($emails !== []) {
            return $emails;
        }

        if (filled($site->org_email)) {
            return [(string) $site->org_email];
        }

        if (filled($site->mail_from_address)) {
            return [(string) $site->mail_from_address];
        }

        $from = (string) config('mail.from.address');

        if ($from !== '' && $from !== 'hello@example.com') {
            return [$from];
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function parseEmails(string $raw): array
    {
        $raw = trim($raw);

        if ($raw === '') {
            return [];
        }

        $parts = preg_split('/[\s,;]+/', $raw) ?: [];
        $emails = [];

        foreach ($parts as $part) {
            $part = trim($part);

            if ($part !== '' && filter_var($part, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $part;
            }
        }

        return array_values(array_unique($emails));
    }
}
