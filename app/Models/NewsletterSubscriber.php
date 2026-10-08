<?php

namespace App\Models;

use App\Models\Concerns\Trashable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsletterSubscriber extends Model
{
    use Trashable;

    protected $fillable = [
        'email',
        'locale',
        'token',
        'unsubscribed_at',
    ];

    protected function casts(): array
    {
        return [
            'unsubscribed_at' => 'datetime',
        ];
    }

    public static function subscribe(string $email, string $locale = 'en'): self
    {
        $subscriber = static::withTrashed()->firstOrNew(['email' => strtolower($email)]);
        if ($subscriber->exists && $subscriber->trashed()) {
            $subscriber->restore();
        }
        $subscriber->locale = $locale;
        $subscriber->token = $subscriber->token ?: Str::random(48);
        $subscriber->unsubscribed_at = null;
        $subscriber->save();

        return $subscriber;
    }

    public function isActive(): bool
    {
        return $this->unsubscribed_at === null;
    }
}
