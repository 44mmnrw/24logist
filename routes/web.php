<?php

use App\Http\Controllers\AppleTouchIconController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CommunityAccountController;
use App\Http\Controllers\CommunityActionController;
use App\Http\Controllers\CommunityCommentController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\CommunityLegalController;
use App\Http\Controllers\CommunityModerationController;
use App\Http\Controllers\CommunityNotificationController;
use App\Http\Controllers\CommunityPostController;
use App\Http\Controllers\CsrfTokenController;
use App\Http\Controllers\FaviconController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LandingLeadController;
use App\Http\Controllers\ManifestController;
use App\Http\Controllers\MaxCommunityAuthController;
use App\Http\Controllers\MaxCommunityWebhookController;
use App\Http\Controllers\OgHeroCardController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PwaIconController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\TelegramCommunityAuthController;
use App\Http\Controllers\VkCommunityAuthController;
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

Route::post('/leads/epd-presentation', [LandingLeadController::class, 'storeEpdPresentation'])
    ->middleware('throttle:12,1')
    ->name('leads.epd-presentation.store');

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

Route::middleware('community.enabled')->prefix('community')->name('community.')->group(function (): void {
    Route::get('/', [CommunityController::class, 'index'])->name('index');
    Route::get('/c/{category}', [CommunityController::class, 'category'])->name('categories.show');
    Route::get('/p/{post}/{slug?}', [CommunityPostController::class, 'show'])
        ->whereNumber('post')->name('posts.show');
    Route::get('/u/{user}', [CommunityAccountController::class, 'profile'])->name('profile');
    Route::get('/rules', [CommunityLegalController::class, 'rules'])->name('rules');
    Route::get('/privacy', [CommunityLegalController::class, 'privacy'])->name('privacy');

    Route::get('/login', [CommunityAccountController::class, 'login'])->name('login');
    Route::get('/auth/telegram', [TelegramCommunityAuthController::class, 'redirect'])->name('auth.telegram.redirect');
    Route::get('/auth/telegram/callback', [TelegramCommunityAuthController::class, 'callback'])->name('auth.telegram.callback');
    Route::get('/auth/vk', [VkCommunityAuthController::class, 'redirect'])->name('auth.vk.redirect');
    Route::get('/auth/vk/callback', [VkCommunityAuthController::class, 'callback'])->name('auth.vk.callback');
    Route::get('/auth/max', [MaxCommunityAuthController::class, 'start'])->name('auth.max.start');
    Route::post('/auth/max/approve', [MaxCommunityAuthController::class, 'approve'])->middleware('throttle:20,1')->name('auth.max.approve');
    Route::post('/auth/max/session', [MaxCommunityAuthController::class, 'session'])->middleware('throttle:20,1')->name('auth.max.session');
    Route::get('/auth/max/status/{challenge}', [MaxCommunityAuthController::class, 'status'])->middleware('throttle:60,1')->name('auth.max.status');

    Route::middleware('community.auth')->group(function (): void {
        Route::get('/onboarding', [CommunityAccountController::class, 'onboarding'])->name('onboarding');
        Route::post('/onboarding', [CommunityAccountController::class, 'completeOnboarding'])->name('onboarding.store');
        Route::post('/logout', [CommunityAccountController::class, 'logout'])->name('logout');

        Route::middleware('community.onboarded')->group(function (): void {
            Route::get('/submit', [CommunityPostController::class, 'create'])->name('posts.create');
            Route::post('/posts', [CommunityPostController::class, 'store'])->middleware('throttle:community-posts')->name('posts.store');
            Route::get('/posts/{post}/edit', [CommunityPostController::class, 'edit'])->name('posts.edit');
            Route::put('/posts/{post}', [CommunityPostController::class, 'update'])->name('posts.update');
            Route::delete('/posts/{post}', [CommunityPostController::class, 'destroy'])->name('posts.destroy');
            Route::post('/posts/{post}/comments', [CommunityCommentController::class, 'store'])->middleware('throttle:community-comments')->name('comments.store');
            Route::put('/comments/{comment}', [CommunityCommentController::class, 'update'])->name('comments.update');
            Route::delete('/comments/{comment}', [CommunityCommentController::class, 'destroy'])->name('comments.destroy');
            Route::post('/actions/vote', [CommunityActionController::class, 'vote'])->middleware('throttle:120,1')->name('vote');
            Route::post('/actions/report', [CommunityActionController::class, 'report'])->middleware('throttle:10,1')->name('report');
            Route::get('/notifications', [CommunityNotificationController::class, 'index'])->name('notifications');
            Route::get('/notifications/{notification}', [CommunityNotificationController::class, 'read'])->name('notifications.read');
            Route::post('/notifications/read-all', [CommunityNotificationController::class, 'readAll'])->name('notifications.read_all');
            Route::get('/settings', [CommunityAccountController::class, 'settings'])->name('settings');
            Route::put('/settings', [CommunityAccountController::class, 'updateSettings'])->name('settings.update');
            Route::delete('/settings/account', [CommunityAccountController::class, 'destroy'])->name('settings.destroy');

            Route::middleware('community.moderator')->prefix('moderation')->name('moderation.')->group(function (): void {
                Route::get('/', [CommunityModerationController::class, 'index'])->name('index');
                Route::post('/reports/{report}', [CommunityModerationController::class, 'act'])->name('act');
            });
        });
    });
});

Route::post('/community/webhooks/max', MaxCommunityWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('community.webhooks.max');
