<?php

namespace Tests\Unit;

use App\Support\LandingDeadlineForm;
use PHPUnit\Framework\TestCase;

class LandingDeadlineFormTest extends TestCase
{
    public function test_it_hydrates_deadline_fields_for_platform_section(): void
    {
        $data = LandingDeadlineForm::hydrate([
            'slug' => 'platform',
            'extra' => [
                'deadline_kicker' => 'ДЕДЛАЙН',
                'deadline_date' => '1 сентября 2026',
                'deadline_icon' => 'icon:calendar-alert',
                'deadline_text' => 'Исходный текст',
                'deadline_button_text' => 'Подробнее',
            ],
        ]);

        $this->assertSame('ДЕДЛАЙН', $data['deadline_kicker']);
        $this->assertSame('1 сентября 2026', $data['deadline_date']);
        $this->assertSame('calendar-alert', $data['deadline_icon']);
        $this->assertSame('Исходный текст', $data['deadline_text']);
        $this->assertSame('Подробнее', $data['deadline_button_text']);
    }

    public function test_it_dehydrates_edited_fields_into_extra(): void
    {
        $data = LandingDeadlineForm::dehydrate([
            'slug' => 'platform',
            'extra' => ['unrelated' => 'preserved'],
            'deadline_kicker' => ' ВАЖНО ',
            'deadline_date' => '2 сентября 2026',
            'deadline_icon' => 'calendar-alert',
            'deadline_text' => ' Новый текст ',
            'deadline_button_text' => '',
        ]);

        $this->assertSame('ВАЖНО', $data['extra']['deadline_kicker']);
        $this->assertSame('2 сентября 2026', $data['extra']['deadline_date']);
        $this->assertSame('icon:calendar-alert', $data['extra']['deadline_icon']);
        $this->assertSame('Новый текст', $data['extra']['deadline_text']);
        $this->assertArrayNotHasKey('deadline_button_text', $data['extra']);
        $this->assertSame('preserved', $data['extra']['unrelated']);
        $this->assertArrayNotHasKey('deadline_text', $data);
    }
}
