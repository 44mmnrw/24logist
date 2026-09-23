<?php

namespace Tests\Feature;

use App\Models\LandingLead;
use App\Services\CommercialOfferPdfService;
use Tests\TestCase;

class CommercialOfferPdfTest extends TestCase
{
    public function test_long_recipient_details_do_not_create_an_extra_page_before_pricing(): void
    {
        $company = mb_substr(str_repeat('Общество с ограниченной ответственностью «Межрегиональная транспортно-экспедиционная компания» ', 3), 0, 255);
        $longName = mb_substr(str_repeat('Александров Константин Константинович ', 8), 0, 255);
        $longEmail = str_repeat('a', 64).'@'.str_repeat('b', 63).'.'.str_repeat('c', 63).'.'.str_repeat('d', 58).'.ru';

        foreach ([
            ['ООО «Тестовая логистика»', 'Иван Петров', 'ivan@example.test'],
            [$company, 'Иван Петров', 'ivan@example.test'],
            [str_repeat('Ш', 255), 'Иван Петров', 'ivan@example.test'],
            [$company, $longName, $longEmail],
        ] as [$companyName, $name, $email]) {
            $lead = new LandingLead([
                'type' => LandingLead::TYPE_COMMERCIAL_OFFER,
                'name' => $name,
                'email' => $email,
                'phone' => '+7 (900) 000-00-00',
                'offer_details' => [
                    'company' => $companyName,
                    'inn' => '7707083893',
                    'plan_title' => 'Команда',
                    'users' => 3,
                    'billing_period' => 'year',
                    'unit_price' => 1200,
                    'options' => [
                        ['title' => 'Счета на оплату', 'price' => 1500],
                        ['title' => 'Нормализация адресов по ФИАС', 'price' => 500],
                        ['title' => 'Интеграция по API', 'price' => 2000],
                    ],
                    'total' => 91200,
                    'currency_suffix' => '₽/год',
                ],
            ]);
            $lead->id = 42;
            $lead->created_at = '2026-09-23 12:00:00';

            $pdf = app(CommercialOfferPdfService::class)->render($lead);

            // Count PDF page objects, excluding the /Pages tree node.
            $this->assertSame(2, preg_match_all('~/Type\s*/Page\b~', $pdf), 'Unexpected page count for '.$companyName);
        }
    }
}
