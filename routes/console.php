<?php

use App\Models\CommunityLoginChallenge;
use App\Services\Referral\ReferralCommissionService;
use App\Services\Referral\ReferralPayoutService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    CommunityLoginChallenge::query()
        ->where('expires_at', '<', now()->subDay())
        ->delete();
})->hourly()->name('community:prune-login-challenges')->withoutOverlapping();

Schedule::call(fn (): int => app(ReferralCommissionService::class)->releaseDue())
    ->dailyAt('02:30')->name('referrals:release-commissions')->withoutOverlapping();

Schedule::call(fn () => app(ReferralPayoutService::class)->createMonthlyRegistry())
    ->monthlyOn(1, '03:00')->name('referrals:create-monthly-registry')->withoutOverlapping();
