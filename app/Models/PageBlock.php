<?php

namespace App\Models;

use App\Models\Concerns\Trashable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class PageBlock extends Model
{
    use HasTranslations;
    use Trashable;

    public array $translatable = ['title', 'body'];

    protected $fillable = ['key', 'page', 'title', 'body', 'image_path', 'sort_order', 'is_published'];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'body' => 'array',
            'is_published' => 'boolean',
        ];
    }
}
