<?php

namespace App\Services\Community;

use DateTimeInterface;

final class CommunityRanking
{
    public static function hotScore(int $score, DateTimeInterface $createdAt): float
    {
        $order = log10(max(abs($score), 1));
        $sign = $score <=> 0;

        return round(($sign * $order) + ($createdAt->getTimestamp() / 45000), 7);
    }
}
