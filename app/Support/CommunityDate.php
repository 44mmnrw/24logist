<?php

namespace App\Support;

use Carbon\CarbonInterface;

final class CommunityDate
{
    private const GENITIVE_MONTHS = [
        1 => 'января',
        2 => 'февраля',
        3 => 'марта',
        4 => 'апреля',
        5 => 'мая',
        6 => 'июня',
        7 => 'июля',
        8 => 'августа',
        9 => 'сентября',
        10 => 'октября',
        11 => 'ноября',
        12 => 'декабря',
    ];

    public static function relative(?CarbonInterface $date): string
    {
        return $date?->locale('ru')->diffForHumans() ?? '';
    }

    public static function monthYear(?CarbonInterface $date): string
    {
        if ($date === null) {
            return '';
        }

        return self::GENITIVE_MONTHS[$date->month].' '.$date->year;
    }
}
