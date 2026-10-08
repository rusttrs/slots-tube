<?php

namespace App\Http\Controllers;

use App\Models\Bonus;
use App\Models\GamePromo;
use App\Models\Post;
use App\Models\Slot;
use App\Support\VisitorCountry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SlotController extends Controller
{
    public function show(Request $request, string $slug): View
    {
        // Locale-prefixed routes pass locale as first param via {locale} — handle both.
        if ($request->route('locale') && $request->route('slug')) {
            $slug = (string) $request->route('slug');
        } elseif ($request->route('slug')) {
            $slug = (string) $request->route('slug');
        }

        $slot = Slot::query()
            ->with([
                'provider',
                'author',
                'reviewer',
                'updatedByAuthor',
                'bonuses' => fn ($q) => $q->where('bonuses.is_published', true),
            ])
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $similar = Slot::query()
            ->published()
            ->when($slot->provider_id, fn ($q) => $q->where('provider_id', $slot->provider_id))
            ->where('id', '!=', $slot->id)
            ->inRandomOrder()
            ->limit(2)
            ->get();

        $providerSlots = $slot->provider_id
            ? Slot::query()
                ->published()
                ->where('provider_id', $slot->provider_id)
                ->where('id', '!=', $slot->id)
                ->latest('created_at')
                ->latest('id')
                ->limit(8)
                ->get()
            : collect();

        $providerGuide = $slot->provider
            ? Post::query()
                ->matchingProvider($slot->provider)
                ->latest('published_at')
                ->latest('id')
                ->first()
            : null;

        $providerGameCount = $slot->provider_id
            ? Slot::query()->published()->where('provider_id', $slot->provider_id)->count()
            : 0;

        $reviews = $slot->publishedReviews()
            ->with('user')
            ->withLikedBy(auth()->id())
            ->limit(40)
            ->get();

        $ratingBuckets = [];
        for ($i = 1; $i <= 5; $i++) {
            $ratingBuckets[$i] = (int) $slot->publishedReviews()->where('rating', $i)->count();
        }

        $gameInfo = collect([
            'provider' => $slot->provider?->displayName(),
            'release_date' => $slot->release_date?->translatedFormat('F Y'),
            'game_type' => $slot->game_type,
            'grid' => $slot->grid,
            'win_system' => $slot->win_system,
            'rtp' => $slot->rtp_text ?: $slot->rtpPercentLabel(),
            'max_win' => $slot->max_win,
            'volatility' => $slot->volatility,
            'stake_range' => $slot->stake_range,
            'features' => $slot->features_text,
            'theme' => $slot->theme_text,
            'wild' => $slot->wild_symbol,
            'free_spins' => $slot->free_spins,
            'progressive' => $slot->progressive,
            'bonus_buy' => $slot->bonus_buy,
            'tumbling' => $slot->tumbling_wins,
            'gamble' => $slot->gamble_feature,
            'scatter' => $slot->scatter_symbol,
            'technology' => $slot->technology,
        ])->filter(fn ($v) => filled($v));

        $visitorCountry = VisitorCountry::code($request);
        $slot->setRelation(
            'bonuses',
            Bonus::forVisitorCountry($slot->bonuses, $visitorCountry)
        );
        $gamePromo = GamePromo::resolveForVisitor($visitorCountry);

        return view('slots.show', [
            'slot' => $slot,
            'similar' => $similar,
            'providerSlots' => $providerSlots,
            'providerGuide' => $providerGuide,
            'providerGameCount' => $providerGameCount,
            'reviews' => $reviews,
            'ratingBuckets' => $ratingBuckets,
            'gameInfo' => $gameInfo,
            'visitorCountry' => $visitorCountry,
            'gamePromo' => $gamePromo,
            'canonical' => rtrim($slot->publicUrl(), '/').'/',
        ]);
    }
}
