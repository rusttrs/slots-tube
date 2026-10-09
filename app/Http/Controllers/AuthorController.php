<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\PageSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthorController extends Controller
{
    public function index(): View
    {
        return view('authors.index', [
            'groups' => Author::teamGroups(),
            'page' => PageSetting::for('authors'),
            'canonical' => rtrim(localized_url(null, 'authors'), '/').'/',
        ]);
    }

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
