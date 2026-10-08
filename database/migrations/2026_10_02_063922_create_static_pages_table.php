<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('static_pages', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->string('slug')->unique();
            $table->json('body')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

    }

    public function down(): void
    {
        // drop pivots first if present
        Schema::dropIfExists('feature_slot');
        Schema::dropIfExists('slot_theme');
        Schema::dropIfExists('static_pages');
    }
};
