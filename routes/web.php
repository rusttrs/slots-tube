<?php

use App\Http\Controllers\AuthorController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\BonusController;
use App\Http\Controllers\ContentHubController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PostEngagementController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SlotController;
use App\Http\Controllers\SlotReviewController;
use App\Http\Controllers\TranslateController;
use App\Http\Controllers\UserProfileController;
use App\Models\Post;
use Illuminate\Support\Facades\Route;

$register = function (?string $namePrefix = null): void {
    $named = $namePrefix === null;

    $get = function (string $uri, $action, string $routeName) use ($named) {
        $route = Route::get($uri, $action);
        if ($named) {
            $route->name($routeName);
        }

        return $route;
    };

    $get('/', [PageController::class, 'home'], 'home');
    $get('/free-slots', fn () => app(PageController::class)->stub('Free Slots'), 'free-slots');
    $get('/crash-games', fn () => app(PageController::class)->stub('Crash Games'), 'crash-games');
    $get('/other-games', fn () => app(PageController::class)->stub('Other Games'), 'other-games');
    $get('/providers', fn () => app(PageController::class)->stub('Providers'), 'providers');
    $provider = Route::get('/providers/{slug}', [ProviderController::class, 'show'])->where('slug', '[A-Za-z0-9\-]+');
    if ($named) {
        $provider->name('providers.show');
    }
    Route::get('/providers/{slug}/', [ProviderController::class, 'show'])->where('slug', '[A-Za-z0-9\-]+');
    $get('/by-feature', fn () => app(PageController::class)->stub('By Feature'), 'by-feature');
    $featureFilter = Route::get('/by-feature/{slug}', fn () => app(PageController::class)->stub('By Feature'))
        ->where('slug', '[A-Za-z0-9\-]+');
    if ($named) {
        $featureFilter->name('by-feature.show');
    }
    Route::get('/by-feature/{slug}/', fn () => app(PageController::class)->stub('By Feature'))
        ->where('slug', '[A-Za-z0-9\-]+');
    $get('/by-themes', fn () => app(PageController::class)->stub('By Themes'), 'by-themes');

    // Content sections (listings) and publications under /content/
    Route::get('/content', [ContentHubController::class, 'index']);
    $get('/content/', [ContentHubController::class, 'index'], 'content');
    foreach (Post::reservedSlugs() as $section) {
        $get('/content/'.$section, [ContentHubController::class, 'section'], $section)->defaults('section', $section);
    }

    Route::get('/content/{slug}', [PostController::class, 'show'])
        ->where('slug', '[A-Za-z0-9\-]+');
    $contentShow = Route::get('/content/{slug}/', [PostController::class, 'show'])
        ->where('slug', '[A-Za-z0-9\-]+');
    if ($named) {
        $contentShow->name('content.show');
    }

    // Legacy flat section URLs → /content/...
    foreach ([
        'news' => 'content/news',
        'blogs' => 'content/blogs',
        'guides' => 'content/guides',
        'streamers' => 'content/streamers',
    ] as $legacy => $target) {
        Route::get('/'.$legacy, fn () => redirect(localized_url(null, $target), 301));
    }
    $get('/authors', [AuthorController::class, 'index'], 'authors');
    $get('/authors/{slug}', [AuthorController::class, 'show'], 'authors.show')->where('slug', '[A-Za-z0-9\-]+');
    $get('/users/{slug}', [UserProfileController::class, 'show'], 'users.show')->where('slug', '[A-Za-z0-9\-]+');
    $get('/our-mission', fn () => app(PageController::class)->stub('Our Mission'), 'our-mission');
    $get('/bonuses', [BonusController::class, 'index'], 'bonuses');
    $get('/search', SearchController::class, 'search')->middleware('throttle:60,1');
    $get('/privacy', fn () => app(PageController::class)->stub('Privacy Policy'), 'privacy');
    $get('/terms', fn () => app(PageController::class)->stub('Terms And Conditions'), 'terms');
    $get('/cookies', fn () => app(PageController::class)->stub('Cookie Policy'), 'cookies');
    $get('/responsible-gaming', fn () => app(PageController::class)->stub('Responsible Gaming'), 'responsible-gaming');

    $slot = Route::get('/slots/{slug}', [SlotController::class, 'show'])->where('slug', '[A-Za-z0-9\-]+');
    if ($named) {
        $slot->name('slots.show');
    }
    // Trailing-slash alias (CanonicalUrl redirects bare URL here)
    Route::get('/slots/{slug}/', [SlotController::class, 'show'])->where('slug', '[A-Za-z0-9\-]+');

    Route::middleware('auth')->group(function () use ($named) {
        $r = Route::get('/profile', [ProfileController::class, 'show']);
        if ($named) {
            $r->name('profile');
        }
        $r = Route::post('/profile', [ProfileController::class, 'update']);
        if ($named) {
            $r->name('profile.update');
        }
        $r = Route::post('/profile/username', [ProfileController::class, 'updateUsername']);
        if ($named) {
            $r->name('profile.username');
        }
        $r = Route::post('/profile/email', [ProfileController::class, 'requestEmailChange'])->middleware('throttle:5,1');
        if ($named) {
            $r->name('profile.email');
        }
        $r = Route::post('/slots/{slug}/reviews', [SlotReviewController::class, 'store'])->where('slug', '[A-Za-z0-9\-]+');
        if ($named) {
            $r->name('slots.reviews.store');
        }
    });
};

$register(null);

Route::prefix('{locale}')
    ->whereIn('locale', ['de', 'fr'])
    ->group(fn () => $register('prefixed'));

Route::middleware(['auth', 'throttle:60,1'])->post('/slot-reviews/{review}/like', [SlotReviewController::class, 'like'])
    ->name('slots.reviews.like');

Route::middleware('auth')->group(function () {
    Route::post('/posts/{post}/like', [PostEngagementController::class, 'likePost'])
        ->middleware('throttle:60,1')
        ->name('posts.like');
    Route::post('/posts/{post}/comments', [PostEngagementController::class, 'storeComment'])
        ->middleware('throttle:30,1')
        ->name('posts.comments.store');
    Route::post('/post-comments/{comment}/like', [PostEngagementController::class, 'likeComment'])
        ->middleware('throttle:60,1')
        ->name('posts.comments.like');
    Route::delete('/post-comments/{comment}', [PostEngagementController::class, 'destroyComment'])
        ->name('posts.comments.destroy');
});

Route::post('/translate', TranslateController::class)
    ->middleware('throttle:40,1')
    ->name('translate');

Route::post('/auth/magic-link', [MagicLinkController::class, 'send'])->name('auth.magic.send');
Route::get('/auth/magic/{user}', [MagicLinkController::class, 'verify'])->middleware('signed')->name('auth.magic.verify');
Route::post('/logout', [MagicLinkController::class, 'logout'])->name('logout');
Route::get('/profile/email/confirm/{user}', [ProfileController::class, 'confirmEmailChange'])
    ->middleware('signed')
    ->name('profile.email.confirm');
Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');

Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');
