<?php

namespace App\Services;

use Closure;
use InvalidArgumentException;

final class EtrnRouletteOutcome
{
    public const LOGISTRU_SYMBOL = 'logistru-bonus';

    private const ROLL_MAX = 999_999;

    private const MATCH_ROLLS = 250_000;

    public function __construct(private readonly ?Closure $random = null) {}

    /** @return array{jackpot: bool, matched: bool, reels: array<int, string>, destination: ?string} */
    public function generate(?callable $random = null): array
    {
        $operators = array_values(config('epd_operators', []));
        if (count($operators) < 2) {
            throw new InvalidArgumentException('At least two ETRN operators are required.');
        }

        $random ??= $this->random ?? static fn (int $max): int => random_int(0, $max);
        $roll = $random(self::ROLL_MAX);
        $matchSymbols = [...$operators, self::LOGISTRU_SYMBOL];

        if ($roll < self::MATCH_ROLLS) {
            $destination = $matchSymbols[$random(count($matchSymbols) - 1)];
            $jackpot = $destination === self::LOGISTRU_SYMBOL;

            return [
                'jackpot' => $jackpot,
                'matched' => true,
                'reels' => array_fill(0, 3, $destination),
                'destination' => $jackpot ? null : $destination,
            ];
        }

        $reels = array_map(static fn (): string => $matchSymbols[$random(count($matchSymbols) - 1)], [0, 1, 2]);
        $bonusSeen = false;
        foreach ($reels as $index => $symbol) {
            if ($symbol !== self::LOGISTRU_SYMBOL) {
                continue;
            }
            if ($bonusSeen) {
                $reels[$index] = $operators[$random(count($operators) - 1)];
            }
            $bonusSeen = true;
        }

        if (count(array_unique($reels)) === 1) {
            $next = (array_search($reels[0], $operators, true) + 1) % count($operators);
            $reels[2] = $operators[$next];
        }

        return ['jackpot' => false, 'matched' => false, 'reels' => $reels, 'destination' => null];
    }

    public function nextJackpotChancePercent(): float
    {
        return (self::MATCH_ROLLS / (self::ROLL_MAX + 1)) * 100 / (count(config('epd_operators', [])) + 1);
    }
}
