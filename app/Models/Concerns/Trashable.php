<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\SoftDeletes;

trait Trashable
{
    use SoftDeletes;
}
