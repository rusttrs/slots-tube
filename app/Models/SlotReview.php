<?php

namespace App\Models;

use App\Models\Concerns\HasLikes;
use App\Models\Concerns\Trashable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlotReview extends Model
{
    use HasLikes;
    use Trashable;

    protected $fillable = [
        'slot_id', 'user_id', 'play_mode', 'rating', 'played_myself', 'body', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'played_myself' => 'boolean',
            'is_published' => 'boolean',
            'rating' => 'integer',
        ];
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(Slot::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isLong(): bool
    {
        return mb_strlen(trim((string) $this->body)) > 180;
    }
}
