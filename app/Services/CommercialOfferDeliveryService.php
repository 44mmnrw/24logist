<?php

namespace App\Services;

use App\Mail\CommercialOfferDocuments;
use App\Models\LandingLead;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CommercialOfferDeliveryService
{
    public function __construct(
        private readonly SiteMailService $mail,
        private readonly CommercialOfferPdfService $pdf,
    ) {}

    public function send(LandingLead $lead): bool
    {
        if ($lead->offer_sent_at !== null) {
            return true;
        }

        try {
            $this->mail->apply();
            $document = $this->pdf->render($lead);

            Mail::to($lead->email)->send(new CommercialOfferDocuments($lead, $document));

            $lead->forceFill(['offer_sent_at' => now()])->save();

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }
}
