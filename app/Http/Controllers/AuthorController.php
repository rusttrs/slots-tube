<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthorController extends Controller
{
    public function show(Request $request, string $slug): View
    {
        $slug = (string) ($request->route('slug') ?: $slug);

        $author = Author::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return view('authors.show', [
            'author' => $author,
            'slots' => $author->latestSlots(),
            'posts' => $author->latestPosts(),
            'favoriteSlots' => $author->favoriteSlots(),
            'redFlagSlots' => $author->redFlagSlots(),
            'topStreamers' => $author->topStreamers(),
            'favoritePosts' => $author->favoritePosts(),
            'canonical' => rtrim($author->publicUrl(), '/').'/',
        ]);
    }
}
