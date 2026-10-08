<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            DELETE FROM slot_reviews AS older
            USING slot_reviews AS newer
            WHERE older.slot_id = newer.slot_id
              AND older.user_id = newer.user_id
              AND older.id < newer.id
        SQL);

        Schema::table('slot_reviews', function (Blueprint $table) {
            $table->unique(['slot_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('slot_reviews', function (Blueprint $table) {
            $table->dropUnique(['slot_id', 'user_id']);
        });
    }
};
