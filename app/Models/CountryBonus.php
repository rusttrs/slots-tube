<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CountryBonus extends Model
{
    protected $table = 'country_bonus';

    protected $fillable = ['country_id', 'bonus_id', 'sort_order'];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function bonus(): BelongsTo
    {
        return $this->belongsTo(Bonus::class);
    }
}
