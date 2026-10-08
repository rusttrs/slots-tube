<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonuses', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->string('slug')->unique();
            $table->string('casino_name')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('cta_url')->nullable();
            $table->json('short_text')->nullable();
            $table->json('terms')->nullable();
            $table->boolean('no_kyc')->default(false);
            $table->boolean('is_exclusive')->default(false);
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

    }

    public function down(): void
    {
        // drop pivots first if present
        Schema::dropIfExists('feature_slot');
        Schema::dropIfExists('slot_theme');
        Schema::dropIfExists('bonuses');
    }
};
