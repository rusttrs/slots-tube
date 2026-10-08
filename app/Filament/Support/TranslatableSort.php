<?php

namespace App\Filament\Support;

use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * Sort a Spatie-translatable JSON column by one locale.
 * Postgres cannot ORDER BY a json column directly.
 */
class TranslatableSort
{
    public static function by(string $column, string $locale = 'en'): Closure
    {
        return function (Builder $query, string $direction) use ($column, $locale): Builder {
            $column = $query->qualifyColumn($column);
            $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

            return $query->orderByRaw("lower({$column}->>?) {$direction}", [$locale]);
        };
    }
}
