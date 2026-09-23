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
file_put_contents(__DIR__.'/commercial-offer-preview.pdf', app(App\Services\CommercialOfferPdfService::class)->render($lead));
echo "PDF preview generated without sending mail.\n";
