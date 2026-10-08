<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (['title', 'short_text', 'terms'] as $column) {
            DB::statement("ALTER TABLE bonuses ALTER COLUMN {$column} TYPE jsonb USING {$column}::jsonb");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (['title', 'short_text', 'terms'] as $column) {
            DB::statement("ALTER TABLE bonuses ALTER COLUMN {$column} TYPE json USING {$column}::json");
        }
    }
};
