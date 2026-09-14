<?php

namespace App\Support;

final class CommunityText
{
    public static function comments(int $count): string
    {
        $lastTwo = $count % 100;
        $last = $count % 10;

        if ($lastTwo >= 11 && $lastTwo <= 14) {
            return 'комментариев';
        }

        return match (true) {
            $last === 1 => 'комментарий',
            $last >= 2 && $last <= 4 => 'комментария',
            default => 'комментариев',
        };
    }
}
