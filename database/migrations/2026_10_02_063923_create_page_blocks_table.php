<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('page')->nullable();
            $table->json('title')->nullable();
            $table->json('body')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

    }

    public function down(): void
    {
        // drop pivots first if present
        Schema::dropIfExists('feature_slot');
        Schema::dropIfExists('slot_theme');
        Schema::dropIfExists('page_blocks');
    }
};
