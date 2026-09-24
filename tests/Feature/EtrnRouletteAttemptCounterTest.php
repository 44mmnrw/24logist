<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EtrnRouletteAttemptCounterTest extends TestCase
{
    use RefreshDatabase;

    public function test_counter_is_shared_and_increments_for_each_spin_request(): void
    {
        $this->getJson(route('etrn-roulette.attempts.index'))
            ->assertOk()
            ->assertExactJson(['attempts' => 0, 'jackpots' => 0]);

        $this->postJson(route('etrn-roulette.attempts.store'))
            ->assertOk()
            ->assertExactJson(['attempts' => 1, 'jackpots' => 0]);

        $this->postJson(route('etrn-roulette.attempts.store'), ['jackpot' => true])
            ->assertOk()
            ->assertExactJson(['attempts' => 2, 'jackpots' => 1]);

        $this->getJson(route('etrn-roulette.attempts.index'))
            ->assertOk()
            ->assertExactJson(['attempts' => 2, 'jackpots' => 1]);
    }
}
