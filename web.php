<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SlotController;
use App\Http\Controllers\SlotReviewController;
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
    $get('/by-feature', fn () => app(PageController::class)->stub('By Feature'), 'by-feature');
    $get('/by-themes', fn () => app(PageController::class)->stub('By Themes'), 'by-themes');
    $get('/streamers', fn () => app(PageController::class)->stub('Streamers'), 'streamers');
    $get('/news', fn () => app(PageController::class)->stub('News'), 'news');
    $get('/guides', fn () => app(PageController::class)->stub('Guides'), 'guides');
    $get('/blogs', fn () => app(PageController::class)->stub('Blogs'), 'blogs');
    $get('/authors', fn () => app(PageController::class)->stub('Our Team'), 'authors');
    $get('/our-mission', fn () => app(PageController::class)->stub('Our Mission'), 'our-mission');
    $get('/bonuses', fn () => app(PageController::class)->stub('Bonuses'), 'bonuses');
    $get('/privacy', fn () => app(PageController::class)->stub('Privacy Policy'), 'privacy');
    $get('/terms', fn () => app(PageController::class)->stub('Terms And Conditions'), 'terms');
    $get('/cookies', fn () => app(PageController::class)->stub('Cookie Policy'), 'cookies');
    $get('/responsible-gaming', fn () => app(PageController::class)->stub('Responsible Gaming'), 'responsible-gaming');

    $slot = Route::get('/slots/{slug}', [SlotController::class, 'show'])->where('slug', '[A-Za-z0-9\-]+');
    if ($named) {
        $slot->name('slots.show');
    }
    // Trailing-slash alias (nginx redirects bare URL here)
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

Route::middleware('auth')->post('/slot-reviews/{review}/like', [SlotReviewController::class, 'like'])
    ->name('slots.reviews.like');

Route::post('/auth/magic-link', [MagicLinkController::class, 'send'])->name('auth.magic.send');
Route::get('/auth/magic/{user}', [MagicLinkController::class, 'verify'])->middleware('signed')->name('auth.magic.verify');
Route::post('/logout', [MagicLinkController::class, 'logout'])->name('logout');
Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');

Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');
