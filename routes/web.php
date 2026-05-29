<?php

use App\Http\Controllers\LandingController;
use App\Http\Controllers\LandingLeadController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class);

Route::post('/leads/quiz', [LandingLeadController::class, 'storeQuiz'])
    ->middleware('throttle:12,1')
    ->name('leads.quiz.store');

Route::post('/leads/contact', [LandingLeadController::class, 'storeContact'])
    ->middleware('throttle:12,1')
    ->name('leads.contact.store');

Route::get('/privacy-policy', [PageController::class, 'show'])
    ->defaults('slug', 'privacy-policy')
    ->name('legal.privacy_policy');

Route::get('/pages/{slug}', [PageController::class, 'show'])
    ->name('pages.show')
    ->where('slug', '[a-z0-9\-]+');
