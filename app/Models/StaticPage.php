<?php

namespace App\Models;

use App\Models\Concerns\Trashable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class StaticPage extends Model
{
    use HasTranslations;
    use Trashable;

    public array $translatable = ['title', 'body'];

    protected $fillable = ['title', 'slug', 'body', 'is_published'];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'body' => 'array',
            'is_published' => 'boolean',
        ];
    }
}
