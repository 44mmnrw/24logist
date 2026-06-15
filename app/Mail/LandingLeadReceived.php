<?php

namespace App\Mail;

use App\Filament\Clusters\Landing\Resources\LandingLeads\LandingLeadResource;
use App\Models\LandingLead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LandingLeadReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public LandingLead $lead,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Новая заявка: '.$this->lead->typeLabel().' — '.$this->lead->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.landing-lead-received',
            with: [
                'adminUrl' => LandingLeadResource::getUrl('view', ['record' => $this->lead], isAbsolute: true),
            ],
        );
    }
}
