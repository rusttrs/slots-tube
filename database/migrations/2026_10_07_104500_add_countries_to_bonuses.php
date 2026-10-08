<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bonuses', function (Blueprint $table) {
            $table->json('countries')->nullable()->after('is_published');
        });

        // Существующие карточки — All countries, чтобы ничего не пропало с сайта.
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement("UPDATE bonuses SET countries = '[\"ALL\"]'::jsonb WHERE countries IS NULL");
        } else {
            DB::table('bonuses')->whereNull('countries')->update(['countries' => json_encode(['ALL'])]);
        }
    }

    public function down(): void
    {
        Schema::table('bonuses', function (Blueprint $table) {
            $table->dropColumn('countries');
        });
    }
};
