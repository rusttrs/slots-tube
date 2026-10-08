<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_promos', function (Blueprint $table) {
            $table->id();
            $table->string('casino_name');
            $table->string('logo_path')->nullable();
            $table->json('offer_text');
            $table->string('cta_url');
            $table->json('cta_label')->nullable();
            $table->json('legal_text')->nullable();
            $table->json('countries')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('delay_seconds')->default(30);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_promos');
    }
};
