<?php

namespace Tests\Unit;

use App\Services\EtrnRouletteOutcome;
use Tests\TestCase;

class EtrnRouletteOutcomeTest extends TestCase
{
    public function test_only_first_five_thousand_of_a_million_rolls_are_initial_jackpots(): void
    {
        $generator = new EtrnRouletteOutcome();
        // The service reads the same operator list that is shown on the game page.
        $this->assertTrue($generator->generate(static fn (int $max): int => 4_999)['jackpot']);
        $this->assertFalse($generator->generate(static fn (int $max): int => $max === 999_999 ? 5_000 : 0)['jackpot']);
    }

    public function test_operator_win_and_miss_never_create_an_accidental_jackpot(): void
    {
        $generator = new EtrnRouletteOutcome();
        $win = $generator->generate(static fn (int $max): int => $max === 999_999 ? 5_000 : 0);
        $this->assertTrue($win['matched']);
        $this->assertFalse($win['jackpot']);
        $this->assertSame(array_fill(0, 3, $win['destination']), $win['reels']);

        $miss = $generator->generate(static fn (int $max): int => $max);
        $this->assertFalse($miss['matched']);
        $this->assertLessThan(3, count(array_filter($miss['reels'],
            static fn (string $symbol): bool => $symbol === EtrnRouletteOutcome::LOGISTRU_SYMBOL)));
        $this->assertGreaterThan(1, count(array_unique($miss['reels'])));
    }

    public function test_jackpot_chance_grows_with_attempts_and_is_capped(): void
    {
        $generator = new EtrnRouletteOutcome();

        $this->assertSame(0.5, $generator->nextJackpotChancePercent(0));
        $this->assertSame(0.51, $generator->nextJackpotChancePercent(1));
        $this->assertSame(1.5, $generator->nextJackpotChancePercent(100));
        $this->assertSame(10.0, $generator->nextJackpotChancePercent(950));
        $this->assertSame(10.0, $generator->nextJackpotChancePercent(100000));

        $this->assertTrue($generator->generate(static fn (int $max): int => 14_999, 100)['jackpot']);
        $this->assertFalse($generator->generate(static fn (int $max): int => $max === 999_999 ? 15_000 : 0, 100)['jackpot']);
    }
}
