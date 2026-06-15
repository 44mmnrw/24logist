<?php

namespace Tests\Unit;

use App\Models\LandingLead;
use App\Models\SiteSetting;
use App\Support\LandingLeadMailTemplate;
use Tests\TestCase;

class LandingLeadMailTemplateTest extends TestCase
{
    public function test_replaces_placeholders(): void
    {
        $lead = new LandingLead([
            'type' => LandingLead::TYPE_QUIZ,
            'name' => 'Анна',
            'phone' => '+7 900 111-22-33',
            'email' => 'anna@example.com',
            'recommended_plan_title' => 'Стандарт',
        ]);

        $site = new SiteSetting([
            'org_brand_name' => 'ЛогистРу',
            'org_email' => 'info@24logist.ru',
            'org_phone' => '+7 (495) 109-25-44',
        ]);

        $result = LandingLeadMailTemplate::render(
            'Здравствуйте, {name}! Тариф: {plan}. {brand} · {type}',
            $lead,
            $site,
        );

        $this->assertSame(
            'Здравствуйте, Анна! Тариф: Стандарт. ЛогистРу · Квиз (просчёт тарифа)',
            $result,
        );
    }
}
