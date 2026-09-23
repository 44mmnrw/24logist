<?php

namespace App\Services;

use App\Models\LandingLead;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class CommercialOfferPdfService
{
    public function render(LandingLead $lead): string
    {
        if ($lead->type !== LandingLead::TYPE_COMMERCIAL_OFFER || empty($lead->offer_details)) {
            throw new InvalidArgumentException('В заявке отсутствуют данные коммерческого предложения.');
        }

        $cache = storage_path('framework/cache/dompdf');
        File::ensureDirectoryExists($cache);

        $options = new Options([
            'defaultFont' => 'DejaVu Sans',
            'isRemoteEnabled' => false,
            'isPhpEnabled' => false,
            'isJavascriptEnabled' => false,
            'isFontSubsettingEnabled' => true,
            'chroot' => [resource_path('documents')],
            'tempDir' => $cache,
            'fontCache' => $cache,
        ]);

        $pdf = new Dompdf($options);
        $pdf->setPaper('A4');
        $pdf->loadHtml(view('pdf.commercial-offer', [
            'lead' => $lead,
            'offer' => $lead->offer_details,
            'signature' => 'data:image/png;base64,'.base64_encode(File::get(resource_path('documents/signature-aristov.png'))),
            'logo' => 'data:image/png;base64,'.base64_encode(File::get(resource_path('documents/logo.png'))),
        ])->render(), 'UTF-8');
        $pdf->render();
        $font = $pdf->getFontMetrics()->getFont('DejaVu Sans');
        $pdf->getCanvas()->page_text(506, 809, '{PAGE_NUM} / {PAGE_COUNT}', $font, 8, [0.41, 0.46, 0.54]);

        return $pdf->output();
    }
}
