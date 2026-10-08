<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostComment;
use App\Services\LikeService;
use App\Support\MediaMirror;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PostEngagementController extends Controller
{
    public function likePost(Request $request, Post $post, LikeService $likes)
    {
        abort_unless($post->is_published, 404);
        abort_unless(Auth::check(), 401);

        ['liked' => $liked, 'count' => $count] = $likes->toggle($request->user(), $post);
        $sample = $post->likes()->with('user')->latest('id')->first();

        return response()->json([
            'ok' => true,
            'liked' => $liked,
            'count' => $count,
            'name' => $sample?->user?->displayName(),
            'label' => $this->likedByLabel($sample?->user?->displayName(), $count),
        ]);
    }

    public function storeComment(Request $request, Post $post)
    {
        abort_unless($post->is_published, 404);
        abort_unless(Auth::check(), 401);

        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:2000', 'required_without:image'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096', 'required_without:body'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('post_comments', 'id')->where(fn ($query) => $query->where('post_id', $post->id)->whereNull('deleted_at')),
            ],
        ]);

        $parentId = isset($data['parent_id']) ? (int) $data['parent_id'] : null;
        if ($parentId) {
            $parent = PostComment::query()->where('post_id', $post->id)->find($parentId);
            if (! $parent || ! $parent->is_published) {
                $parentId = null;
            }
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('comments', 'r2');
            if (is_string($imagePath)) {
                MediaMirror::mirrorPath($imagePath);
            }
        }

        $comment = PostComment::query()->create([
            'post_id' => $post->id,
            'user_id' => Auth::id(),
            'parent_id' => $parentId,
            'body' => trim((string) ($data['body'] ?? '')) ?: null,
            'image_path' => $imagePath,
            'is_published' => true,
        ]);

        return redirect()->to(rtrim($post->publicUrl(), '/').'/#comment-'.$comment->id);
    }

    public function likeComment(Request $request, PostComment $comment, LikeService $likes)
    {
        abort_unless($comment->is_published && $comment->post?->is_published, 404);
        abort_unless(Auth::check(), 401);

        ['liked' => $liked, 'count' => $count] = $likes->toggle($request->user(), $comment);

        return response()->json([
            'ok' => true,
            'liked' => $liked,
            'count' => $count,
            'label' => trans_choice('post.likes', $count, ['count' => $count]),
        ]);
    }

    public function destroyComment(PostComment $comment)
    {
        abort_unless(Auth::check(), 401);
        abort_unless((int) $comment->user_id === (int) Auth::id(), 403);

        $post = $comment->post;
        $comment->delete();

        return redirect()->to($post ? rtrim($post->publicUrl(), '/').'/#post-comments' : url('/'));
    }

    private function likedByLabel(?string $name, int $count): string
    {
        if ($count < 1 || blank($name)) {
            return '';
        }

        if ($count === 1) {
            return __('post.liked_by', ['name' => $name]);
        }

        return trans_choice('post.liked_by_others', $count - 1, [
            'name' => $name,
            'count' => $count - 1,
        ]);
    }
}
