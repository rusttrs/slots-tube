<?php

namespace App\Models;

use App\Models\Concerns\Trashable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Theme extends Model
{
    use HasTranslations;
    use Trashable;

    public array $translatable = ['name', 'description'];

    protected $fillable = ['name', 'slug', 'description', 'is_published', 'sort_order'];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'is_published' => 'boolean',
        ];
    }
}
