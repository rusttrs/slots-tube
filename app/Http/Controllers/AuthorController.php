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
        $page = PageSetting::for('authors');

        return view('authors.index', [
            'sections' => Author::teamSections($page),
            'page' => $page,
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
            'worksCount' => $author->slots()->published()->count() + $author->posts()->where('is_published', true)->count(),
            'canonical' => rtrim($author->publicUrl(), '/').'/',
        ]);
    }
}
