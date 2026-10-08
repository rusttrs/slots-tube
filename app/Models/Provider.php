<?php

namespace App\Models;

use App\Models\Concerns\Trashable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Provider extends Model
{
    use HasTranslations;
    use Trashable;

    public array $translatable = ['name', 'description'];

    protected $fillable = ['name', 'slug', 'logo_path', 'description', 'is_published', 'sort_order'];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'is_published' => 'boolean',
        ];
    }


    public function slots(): HasMany
    {
        return $this->hasMany(Slot::class);
    }

    public function displayName(?string $locale = null): string
    {
        return (string) ($this->getTranslation('name', $locale ?: app()->getLocale())
            ?: $this->getTranslation('name', 'en')
            ?: $this->slug);
    }

    public function logoUrl(): ?string
    {
        if (! filled($this->logo_path)) {
            return null;
        }

        return media_url($this->logo_path);
    }

    public function publicUrl(?string $locale = null): string
    {
        return localized_url($locale, 'providers/'.$this->slug);
    }
}
