<?php

use App\Http\Controllers\ReferralApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/referrals')->middleware(['referral.platform', 'throttle:120,1'])->group(function (): void {
    Route::post('/registrations', [ReferralApiController::class, 'registration']);
    Route::post('/registrations/confirm', [ReferralApiController::class, 'confirm']);
    Route::get('/discounts/{accountId}', [ReferralApiController::class, 'discount'])->where('accountId', '[A-Za-z0-9._:-]+');
    Route::post('/payments', [ReferralApiController::class, 'payment']);
    Route::post('/refunds', [ReferralApiController::class, 'refund']);
});
