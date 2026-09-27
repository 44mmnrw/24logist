<?php

namespace Tests\Unit;

use App\Services\EtrnRouletteOutcome;
use Tests\TestCase;

class EtrnRouletteOutcomeTest extends TestCase
{
    public function test_each_matching_triple_uses_the_same_probability(): void
    {
        $generator = new EtrnRouletteOutcome();
        $operators = array_values(config('epd_operators'));

        foreach ([...$operators, EtrnRouletteOutcome::LOGISTRU_SYMBOL] as $index => $symbol) {
            $outcome = $generator->generate(static fn (int $max): int => $max === 999_999 ? 0 : $index);

            $this->assertTrue($outcome['matched']);
            $this->assertSame(array_fill(0, 3, $symbol), $outcome['reels']);
            $this->assertSame($symbol === EtrnRouletteOutcome::LOGISTRU_SYMBOL, $outcome['jackpot']);
        }

        $this->assertSame(25 / (count($operators) + 1), $generator->nextJackpotChancePercent());
    }

    public function test_operator_win_and_miss_never_create_an_accidental_jackpot(): void
    {
        $generator = new EtrnRouletteOutcome();
        $win = $generator->generate(static fn (int $max): int => $max === 999_999 ? 249_999 : 0);
        $this->assertTrue($win['matched']);
        $this->assertFalse($win['jackpot']);
        $this->assertSame(array_fill(0, 3, $win['destination']), $win['reels']);

        $miss = $generator->generate(static fn (int $max): int => $max);
        $this->assertFalse($miss['matched']);
        $this->assertLessThan(3, count(array_filter($miss['reels'],
            static fn (string $symbol): bool => $symbol === EtrnRouletteOutcome::LOGISTRU_SYMBOL)));
        $this->assertGreaterThan(1, count(array_unique($miss['reels'])));
    }

    public function test_rolls_outside_the_matching_pool_do_not_win(): void
    {
        $generator = new EtrnRouletteOutcome();

        $this->assertFalse($generator->generate(static fn (int $max): int => $max === 999_999 ? 250_000 : 0)['matched']);
        $this->assertFalse($generator->generate(static fn (int $max): int => $max === 999_999 ? 999_999 : 0)['jackpot']);
    }
}
