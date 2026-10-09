<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserProfileController extends Controller
{
    private const LIST_LIMIT = 20;

    public function show(Request $request, string $slug): View
    {
        $slug = (string) ($request->route('slug') ?: $slug);

        $user = User::query()
            ->where('slug', $slug)
            ->whereNull('deactivated_at')
            ->firstOrFail();

        $reviews = $user->slotReviews()
            ->where('is_published', true)
            ->whereHas('slot', fn (Builder $q) => $q->where('is_published', true));

        $comments = $user->postComments()
            ->where('is_published', true)
            ->whereHas('post', fn (Builder $q) => $q->where('is_published', true));

        return view('users.show', [
            'user' => $user,
            'isOwn' => (int) $request->user()?->id === (int) $user->id,
            'reviewsCount' => (clone $reviews)->count(),
            'commentsCount' => (clone $comments)->count(),
            'reviews' => $reviews->with('slot')->latest()->limit(self::LIST_LIMIT)->get(),
            'comments' => $comments->with('post')->latest()->limit(self::LIST_LIMIT)->get(),
            'canonical' => $user->publicUrl(),
        ]);
    }
}
