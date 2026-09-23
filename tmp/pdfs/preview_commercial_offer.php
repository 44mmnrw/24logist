<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$lead = new App\Models\LandingLead([
    'type' => App\Models\LandingLead::TYPE_COMMERCIAL_OFFER,
    'name' => 'Иван Петров',
    'phone' => '+7 (900) 000-00-00',
    'email' => 'ivan@example.test',
    'offer_details' => [
        'company' => 'ООО «Тестовая логистика»',
        'inn' => '7707083893',
        'plan_title' => 'Команда',
        'users' => 3,
        'billing_period' => 'year',
        'period_months' => 12,
        'unit_price' => 1200,
        'options' => [
            ['id' => 1, 'title' => 'Счета на оплату', 'price' => 1500],
            ['id' => 2, 'title' => 'Нормализация адресов по ФИАС', 'price' => 500],
            ['id' => 3, 'title' => 'Интеграция по API', 'price' => 2000],
        ],
        'total' => 91200,
        'currency_suffix' => '₽/год',
    ],
]);
$lead->id = 42;
$lead->created_at = '2026-09-23 12:00:00';
$longRecipient = in_array('--long-recipient', $argv, true);
if ($longRecipient) {
    $lead->name = mb_substr(str_repeat('Александров Константин Константинович ', 8), 0, 255);
    $lead->email = str_repeat('a', 64).'@'.str_repeat('b', 63).'.'.str_repeat('c', 63).'.'.str_repeat('d', 58).'.ru';
    $details = $lead->offer_details;
    $details['company'] = mb_substr(str_repeat('Общество с ограниченной ответственностью «Межрегиональная транспортно-экспедиционная компания» ', 3), 0, 255);
    $lead->offer_details = $details;
}
$filename = $longRecipient ? 'commercial-offer-long-preview.pdf' : 'commercial-offer-preview.pdf';
if (in_array('--long-company', $argv, true)) {
    $details = $lead->offer_details;
    $details['company'] = 'Общество с ограниченной ответственностью «Межрегиональная транспортно-экспедиционная компания комплексного логистического сопровождения промышленных предприятий Северо-Западного региона»';
    $lead->offer_details = $details;
    $filename = 'commercial-offer-long-company-preview.pdf';
}
if (in_array('--unbroken-company', $argv, true)) {
    $details = $lead->offer_details;
    $details['company'] = str_repeat('Ш', 255);
    $lead->offer_details = $details;
    $filename = 'commercial-offer-unbroken-company-preview.pdf';
}
file_put_contents(__DIR__.'/'.$filename, app(App\Services\CommercialOfferPdfService::class)->render($lead));
echo "PDF preview generated without sending mail.\n";
