<?php

namespace App\Http\Controllers;

use App\Models\Slot;
use App\Models\SlotReview;
use App\Services\LikeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SlotReviewController extends Controller
{
    public function store(Request $request, string $slug)
    {
        $slot = Slot::query()->where('slug', $slug)->where('is_published', true)->firstOrFail();

        abort_unless(Auth::check(), 401);

        $data = $request->validate([
            'play_mode' => ['required', Rule::in(['demo', 'real'])],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'played_myself' => ['sometimes', 'boolean'],
            'body' => ['nullable', 'string', 'max:5000'],
        ]);

        $review = SlotReview::query()->updateOrCreate(
            [
                'slot_id' => $slot->id,
                'user_id' => Auth::id(),
            ],
            [
                'play_mode' => $data['play_mode'],
                'rating' => (int) $data['rating'],
                'played_myself' => (bool) ($data['played_myself'] ?? false),
                'body' => $data['body'] ?? null,
                'is_published' => true,
            ],
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => 'Thank you for your feedback!',
                'average' => $slot->averageRating(),
                'count' => $slot->reviewsCount(),
                'review_id' => $review->id,
            ]);
        }

        return back()->with('review_thanks', true);
    }

    public function like(Request $request, SlotReview $review, LikeService $likes)
    {
        abort_unless($review->is_published, 404);

        ['liked' => $liked, 'count' => $count] = $likes->toggle($request->user(), $review);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'liked' => $liked, 'count' => $count]);
        }

        return back();
    }
}
