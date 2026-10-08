<?php

namespace App\Models;

use App\Models\Concerns\Trashable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Bonus extends Model
{
    use HasTranslations;
    use Trashable;

    public array $translatable = ['title', 'short_text', 'terms'];

    protected $fillable = [
        'title', 'slug', 'casino_name', 'logo_path', 'cta_url', 'website_url', 'features',
        'short_text', 'extra_text', 'terms', 'no_kyc', 'is_exclusive', 'is_hot', 'is_new',
        'is_published', 'sort_order', 'countries',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'short_text' => 'array',
            'terms' => 'array',
            'countries' => 'array',
            'features' => 'array',
            'no_kyc' => 'boolean',
            'is_exclusive' => 'boolean',
            'is_hot' => 'boolean',
            'is_new' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    /**
     * Плашки на карточке страницы бонусов. Ключ хранится в БД, подпись — как в макете.
     *
     * @return array<string, string>
     */
    public static function featureOptions(): array
    {
        return [
            'regular_offers' => 'Regular offers',
            'live_casino' => 'Live casino',
            'live_chat' => 'Live chat',
            'vip_program' => 'VIP program',
        ];
    }

    /**
     * @return list<string>
     */
    public function featureKeys(): array
    {
        $allowed = array_keys(static::featureOptions());

        return collect($this->features ?? [])
            ->map(fn ($key) => (string) $key)
            ->filter(fn (string $key) => in_array($key, $allowed, true))
            ->unique()
            ->values()
            ->all();
    }

    public static function allCountriesCode(): string
    {
        return (string) config('bonus_countries.all_code', 'ALL');
    }

    /**
     * @return array<string, string>
     */
    public static function countryOptions(): array
    {
        /** @var array<string, string> $options */
        $options = config('bonus_countries.options', ['ALL' => 'All countries']);

        return $options;
    }

    /**
     * @return list<string>
     */
    public function countryCodes(): array
    {
        $codes = collect($this->countries ?? [])
            ->map(fn ($code) => strtoupper(trim((string) $code)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $codes !== [] ? $codes : [static::allCountriesCode()];
    }

    public function isAllCountries(): bool
    {
        return in_array(static::allCountriesCode(), $this->countryCodes(), true);
    }

    public function targetsCountry(string $country): bool
    {
        $country = strtoupper(trim($country));
        if ($country === '' || $country === static::allCountriesCode()) {
            return false;
        }

        return in_array($country, $this->countryCodes(), true);
    }

    /**
     * Сначала бонусы под страну посетителя; если пусто — All countries.
     *
     * @param  \Illuminate\Support\Collection<int, Bonus>  $bonuses
     * @return \Illuminate\Support\Collection<int, Bonus>
     */
    public static function forVisitorCountry($bonuses, string $country)
    {
        $country = strtoupper(trim($country));
        $items = collect($bonuses)->values();

        if ($country !== '' && $country !== static::allCountriesCode()) {
            $specific = $items->filter(fn (Bonus $bonus) => $bonus->targetsCountry($country))->values();
            if ($specific->isNotEmpty()) {
                return $specific;
            }
        }

        return $items->filter(fn (Bonus $bonus) => $bonus->isAllCountries())->values();
    }

    protected static function booted(): void
    {
        static::saving(function (Bonus $bonus): void {
            $name = trim((string) $bonus->casino_name);

            if ($name !== '' && blank($bonus->getTranslation('title', 'en', false))) {
                $bonus->setTranslation('title', 'en', $name);
            }

            if (blank($bonus->slug)) {
                $bonus->slug = static::uniqueSlug($name !== '' ? $name : 'casino', $bonus->id);
            }
        });
    }

    public static function uniqueSlug(string $source, mixed $ignoreId = null): string
    {
        $base = Str::slug($source) ?: 'casino';
        $slug = $base;
        $i = 2;

        while (static::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    public function slots(): BelongsToMany
    {
        return $this->belongsToMany(Slot::class)
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    public function displayTitle(?string $locale = null): string
    {
        return (string) ($this->getTranslation('title', $locale ?: app()->getLocale())
            ?: $this->getTranslation('title', 'en')
            ?: $this->casino_name
            ?: $this->slug);
    }
}
