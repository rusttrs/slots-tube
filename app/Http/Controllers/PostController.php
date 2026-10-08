<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PostController extends Controller
{
    public function show(Request $request, string $slug): View
    {
        $slug = (string) ($request->route('slug') ?: $slug);
        abort_if(in_array($slug, Post::reservedSlugs(), true), 404);

        $userId = Auth::id() ?? 0;

        $post = Post::query()
            ->with('author')
            ->withCount(['comments as published_comments_count' => fn ($query) => $query->where('is_published', true)])
            ->withLikedBy($userId)
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $likeSamples = $post->likes()->with('user')->latest('id')->limit(3)->get();

        $comments = PostComment::query()
            ->where('post_id', $post->id)
            ->where('is_published', true)
            ->with('user')
            ->withLikedBy($userId)
            ->orderBy('id')
            ->limit(300)
            ->get();

        $byParent = $comments->groupBy(fn (PostComment $comment) => $comment->parent_id ?? 0);
        $roots = $byParent->get(0, collect());

        $thread = function (PostComment $comment) use (&$thread, $byParent) {
            return $byParent->get($comment->id, collect())
                ->flatMap(fn (PostComment $child) => collect([$child])->merge($thread($child)))
                ->values();
        };

        $threads = $roots->mapWithKeys(fn (PostComment $root) => [$root->id => $thread($root)]);

        return view('posts.show', [
            'post' => $post,
            'likeSamples' => $likeSamples,
            'roots' => $roots,
            'threads' => $threads,
            'viewer' => Auth::user(),
            'canonical' => rtrim($post->publicUrl(), '/').'/',
        ]);
    }
}
