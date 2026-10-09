<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('country_bonus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bonus_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['country_id', 'bonus_id']);
        });

        $now = now();
        $countries = [
            ['ALL', 'All countries'],
            ['CA', 'Canada'],
            ['US', 'United States'],
            ['GB', 'United Kingdom'],
            ['DE', 'Germany'],
            ['FR', 'France'],
        ];
        foreach ($countries as $i => [$code, $name]) {
            DB::table('countries')->insert([
                'code' => $code,
                'name' => $name,
                'is_active' => true,
                'sort_order' => $i,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('country_bonus');
        Schema::dropIfExists('countries');
    }
};
