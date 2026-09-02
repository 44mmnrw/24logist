<?php

use App\Models\CommunityLoginChallenge;
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
