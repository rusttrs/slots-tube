<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Like extends Model
{
    public const UPDATED_AT = null;

    /**
     * Morph alias => model. Aliases are stored in likes.likeable_type.
     *
     * @var array<string, class-string<Model>>
     */
    public const TYPES = [
        'post' => Post::class,
        'post_comment' => PostComment::class,
        'slot_review' => SlotReview::class,
    ];

    /** @var array<string, string> */
    public const TYPE_LABELS = [
        'post' => 'Статья',
        'post_comment' => 'Комментарий к статье',
        'slot_review' => 'Отзыв о слоте',
    ];

    protected $fillable = ['user_id', 'likeable_type', 'likeable_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function likeable(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->likeable_type] ?? $this->likeable_type;
    }
}
