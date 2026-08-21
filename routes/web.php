<?php

use App\Http\Controllers\AppleTouchIconController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CsrfTokenController;
use App\Http\Controllers\FaviconController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LandingLeadController;
use App\Http\Controllers\ManifestController;
use App\Http\Controllers\OgHeroCardController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PwaIconController;
use App\Http\Controllers\SeoController;
use App\Http\Middleware\CachePublicLandingPage;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::get('/__og/hero-card', OgHeroCardController::class)
    ->name('og.hero.card');

Route::get('/favicon.ico', FaviconController::class)->name('favicon');
Route::get('/apple-touch-icon.png', AppleTouchIconController::class)->name('apple-touch-icon');
Route::get('/apple-touch-icon-precomposed.png', AppleTouchIconController::class);

Route::get('/site.webmanifest', ManifestController::class)->name('manifest');
Route::get('/icons/icon-{size}.png', PwaIconController::class)
    ->whereNumber('size')
    ->where('size', '192|512');

Route::get('/robots.txt', [SeoController::class, 'robots'])->name('seo.robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('seo.sitemap');
Route::get('/llms.txt', [SeoController::class, 'llms'])->name('seo.llms');

Route::get('/', LandingController::class)
    ->withoutMiddleware([
        StartSession::class,
        ShareErrorsFromSession::class,
        PreventRequestForgery::class,
    ])
    ->middleware(CachePublicLandingPage::class);

Route::get('/csrf-token', CsrfTokenController::class)
    ->name('csrf.token');

Route::post('/leads/quiz', [LandingLeadController::class, 'storeQuiz'])
    ->middleware('throttle:12,1')
    ->name('leads.quiz.store');

Route::post('/leads/contact', [LandingLeadController::class, 'storeContact'])
    ->middleware('throttle:12,1')
    ->name('leads.contact.store');

Route::get('/blog', [BlogController::class, 'index'])
    ->name('blog.index');

Route::get('/tag', [BlogController::class, 'legacyTag'])
    ->name('blog.tag.legacy');

Route::get('/tag/{blogTag:slug}', [BlogController::class, 'tag'])
    ->where('blogTag', '[a-z0-9\-]+')
    ->name('blog.tag');

Route::get('/blog/category/{blogCategory:slug}', [BlogController::class, 'category'])
    ->where('blogCategory', '[a-z0-9\-]+')
    ->name('blog.category');

Route::get('/blog/preview/{blogPost:slug}', [BlogController::class, 'preview'])
    ->middleware(['signed', 'throttle:60,1'])
    ->where('blogPost', '[a-z0-9\-]+')
    ->name('blog.preview');

Route::get('/blog/{slug}', [BlogController::class, 'show'])
    ->name('blog.show')
    ->where('slug', '[a-z0-9\-]+');

Route::get('/privacy-policy', [PageController::class, 'show'])
    ->defaults('slug', 'privacy-policy')
    ->name('legal.privacy_policy');

Route::get('/pages/{slug}', [PageController::class, 'show'])
    ->name('pages.show')
    ->where('slug', '[a-z0-9\-]+');
