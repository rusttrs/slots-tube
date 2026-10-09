<?php

namespace App\Models;

use App\Models\Concerns\Trashable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Страна посетителя (код из Cloudflare CF-IPCountry) и её рекомендуемые бонусы в поиске.
 * Запись с кодом ALL — для посетителей, чьей страны нет в списке.
 */
class Country extends Model
{
    use Trashable;

    public const SEARCH_BONUS_LIMIT = 3;

    protected $fillable = ['code', 'name', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Country $country): void {
            $country->code = strtoupper(trim((string) $country->code));
        });
    }

    public function displayName(): string
    {
        return trim($this->name.' ('.$this->code.')');
    }

    public function searchBonusItems(): HasMany
    {
        return $this->hasMany(CountryBonus::class)->orderBy('sort_order')->orderBy('id');
    }

    public function searchBonuses(): BelongsToMany
    {
        return $this->belongsToMany(Bonus::class, 'country_bonus')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Коды и названия для выбора стран у бонусов и попапов; ALL всегда первым.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $all = Bonus::allCountriesCode();
        $options = static::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'code')
            ->all();

        return [$all => $options[$all] ?? 'All countries'] + $options;
    }

    /**
     * Бонусы блока Recommended в поиске: список страны посетителя, иначе список ALL.
     *
     * @return Collection<int, Bonus>
     */
    public static function recommendedBonuses(string $code): Collection
    {
        $code = strtoupper(trim($code));
        $all = Bonus::allCountriesCode();

        $countries = static::query()
            ->where('is_active', true)
            ->whereIn('code', array_unique([$code, $all]))
            ->with(['searchBonuses' => fn ($query) => $query->where('is_published', true)])
            ->get()
            ->keyBy('code');

        foreach (array_unique([$code, $all]) as $candidate) {
            $bonuses = $countries->get($candidate)?->searchBonuses ?? collect();
            if ($bonuses->isNotEmpty()) {
                return $bonuses->take(self::SEARCH_BONUS_LIMIT)->values();
            }
        }

        return collect();
    }
}
