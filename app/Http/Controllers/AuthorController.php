<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Post;
use App\Models\Slot;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthorController extends Controller
{
    private const LATEST_SLOTS = 12;

    private const LATEST_POSTS = 6;

    public function show(Request $request, string $slug): View
    {
        $slug = (string) ($request->route('slug') ?: $slug);

        $author = Author::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $slots = Slot::query()
            ->published()
            ->where('author_id', $author->id)
            ->latest('created_at')
            ->limit(self::LATEST_SLOTS)
            ->get();

        $posts = Post::query()
            ->where('is_published', true)
            ->where('author_id', $author->id)
            ->with('author')
            ->latest('created_at')
            ->limit(self::LATEST_POSTS)
            ->get();

        return view('authors.show', [
            'author' => $author,
            'slots' => $slots,
            'posts' => $posts,
            'favoriteSlots' => $author->favoriteSlots(),
            'redFlagSlots' => $author->redFlagSlots(),
            'topStreamers' => $author->topStreamers(),
            'favoritePosts' => $author->favoritePosts(),
            'canonical' => rtrim($author->publicUrl(), '/').'/',
        ]);
    }
}
