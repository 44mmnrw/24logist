<?php

namespace App\Services;

use Closure;
use InvalidArgumentException;

final class EtrnRouletteOutcome
{
    public const LOGISTRU_SYMBOL = 'logistru-bonus';

    private const ROLL_MAX = 999_999;

    private const BASE_JACKPOT_ROLLS = 5_000;

    private const JACKPOT_ROLLS_PER_ATTEMPT = 100;

    private const MAX_JACKPOT_ROLLS = 100_000;

    public function __construct(private readonly ?Closure $random = null) {}

    /** @return array{jackpot: bool, matched: bool, reels: array<int, string>, destination: ?string} */
    public function generate(?callable $random = null, int $attemptsWithoutJackpot = 0): array
    {
        $operators = array_values(config('epd_operators', []));
        if (count($operators) < 2) {
            throw new InvalidArgumentException('At least two ETRN operators are required.');
        }

        $random ??= $this->random ?? static fn (int $max): int => random_int(0, $max);
        $roll = $random(self::ROLL_MAX);
        $jackpotThreshold = $this->jackpotThreshold($attemptsWithoutJackpot);

        if ($roll < $jackpotThreshold) {
            return [
                'jackpot' => true,
                'matched' => true,
                'reels' => array_fill(0, 3, self::LOGISTRU_SYMBOL),
                'destination' => null,
            ];
        }

        if ($roll < $jackpotThreshold + 250_000) {
            $destination = $operators[$random(count($operators) - 1)];

            return [
                'jackpot' => false,
                'matched' => true,
                'reels' => array_fill(0, 3, $destination),
                'destination' => $destination,
            ];
        }

        $symbols = [...$operators, self::LOGISTRU_SYMBOL, self::LOGISTRU_SYMBOL, self::LOGISTRU_SYMBOL];
        $reels = array_map(static fn (): string => $symbols[$random(count($symbols) - 1)], [0, 1, 2]);
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

    public function nextJackpotChancePercent(int $attemptsWithoutJackpot): float
    {
        return $this->jackpotThreshold($attemptsWithoutJackpot) / 10_000;
    }

    private function jackpotThreshold(int $attemptsWithoutJackpot): int
    {
        return min(
            self::MAX_JACKPOT_ROLLS,
            self::BASE_JACKPOT_ROLLS + min(
                intdiv(self::MAX_JACKPOT_ROLLS - self::BASE_JACKPOT_ROLLS, self::JACKPOT_ROLLS_PER_ATTEMPT),
                max(0, $attemptsWithoutJackpot),
            ) * self::JACKPOT_ROLLS_PER_ATTEMPT,
        );
    }
}
