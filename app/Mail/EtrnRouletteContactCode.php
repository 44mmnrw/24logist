<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class EtrnRouletteContactCode extends Mailable
{
    public function __construct(public readonly string $code) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Подтверждение email для игры «Рулетка роуминга»');
    }

    public function content(): Content
    {
        return new Content(text: 'mail.etrn-roulette-contact-code');
    }
}
