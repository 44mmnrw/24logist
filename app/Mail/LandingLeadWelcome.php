<?php

namespace App\Mail;

use App\Models\LandingLead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LandingLeadWelcome extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public LandingLead $lead,
        public string $subjectLine,
        public string $bodyText,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.landing-lead-welcome',
            with: [
                'bodyText' => $this->bodyText,
            ],
        );
    }
}
