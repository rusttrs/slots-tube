<?php

namespace App\Models;

use App\Models\Concerns\Trashable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class GamePromo extends Model
{
    use HasTranslations;
    use Trashable;

    public array $translatable = ['offer_text', 'cta_label', 'legal_text'];

    protected $fillable = [
        'casino_name',
        'logo_path',
        'offer_text',
        'cta_url',
        'cta_label',
        'legal_text',
        'countries',
        'is_published',
        'sort_order',
        'delay_seconds',
    ];

    protected function casts(): array
    {
        return [
            'offer_text' => 'array',
            'cta_label' => 'array',
            'legal_text' => 'array',
            'countries' => 'array',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
            'delay_seconds' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (GamePromo $promo): void {
            $codes = collect($promo->countries ?? [])
                ->map(fn ($code) => strtoupper(trim((string) $code)))
                ->filter()
                ->unique()
                ->values()
                ->all();

            $promo->countries = $codes !== [] ? $codes : [Bonus::allCountriesCode()];

            if ((int) $promo->delay_seconds < 1) {
                $promo->delay_seconds = (int) config('game_promo.default_delay_seconds', 30);
            }
        });

        static::saved(function (GamePromo $promo): void {
            if (filled($promo->logo_path)) {
                \App\Support\MediaMirror::mirrorPath((string) $promo->logo_path);
            }
        });
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

        return $codes !== [] ? $codes : [Bonus::allCountriesCode()];
    }

    public function isAllCountries(): bool
    {
        return in_array(Bonus::allCountriesCode(), $this->countryCodes(), true);
    }

    public function targetsCountry(string $country): bool
    {
        $country = strtoupper(trim($country));
        if ($country === '' || $country === Bonus::allCountriesCode()) {
            return false;
        }

        return in_array($country, $this->countryCodes(), true);
    }

    /**
     * Сначала промо под страну посетителя; если пусто — All countries.
     *
     * @param  \Illuminate\Support\Collection<int, GamePromo>  $promos
     * @return \Illuminate\Support\Collection<int, GamePromo>
     */
    public static function forVisitorCountry($promos, string $country)
    {
        $country = strtoupper(trim($country));
        $items = collect($promos)->values();

        if ($country !== '' && $country !== Bonus::allCountriesCode()) {
            $specific = $items->filter(fn (GamePromo $promo) => $promo->targetsCountry($country))->values();
            if ($specific->isNotEmpty()) {
                return $specific;
            }
        }

        return $items->filter(fn (GamePromo $promo) => $promo->isAllCountries())->values();
    }

    public static function resolveForVisitor(string $country): ?self
    {
        $items = static::query()
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return static::forVisitorCountry($items, $country)->first();
    }

    public function translated(string $field, ?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();

        return (string) ($this->getTranslation($field, $locale)
            ?: $this->getTranslation($field, 'en')
            ?: '');
    }

    public function delayMs(): int
    {
        $seconds = (int) ($this->delay_seconds ?: config('game_promo.default_delay_seconds', 30));

        return max(1, $seconds) * 1000;
    }
}
