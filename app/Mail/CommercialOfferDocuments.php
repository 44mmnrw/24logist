<?php

namespace App\Mail;

use App\Models\LandingLead;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CommercialOfferDocuments extends Mailable
{
    public function __construct(
        public LandingLead $lead,
        public string $offerPdf,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Ваше коммерческое предложение — логистРу');
    }

    public function content(): Content
    {
        return new Content(text: 'mail.commercial-offer-documents');
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn (): string => $this->offerPdf, 'логистРу — Коммерческое предложение.pdf')
                ->withMime('application/pdf'),
            Attachment::fromPath(resource_path('documents/functional-characteristics.pdf'))
                ->as('логистРу — Функциональные характеристики.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
