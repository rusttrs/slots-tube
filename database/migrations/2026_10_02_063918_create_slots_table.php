<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained()->nullOnDelete();
            $table->json('title');
            $table->string('slug')->unique();
            $table->string('cover_path')->nullable();
            $table->string('demo_url')->nullable();
            $table->json('excerpt')->nullable();
            $table->json('body')->nullable();
            $table->decimal('rtp', 5, 2)->nullable();
            $table->string('volatility')->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_new')->default(false);
            $table->boolean('is_popular')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('feature_slot', function (Blueprint $table) {
            $table->foreignId('feature_id')->constrained()->cascadeOnDelete();
            $table->foreignId('slot_id')->constrained()->cascadeOnDelete();
            $table->primary(['feature_id', 'slot_id']);
        });

        Schema::create('slot_theme', function (Blueprint $table) {
            $table->foreignId('slot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('theme_id')->constrained()->cascadeOnDelete();
            $table->primary(['slot_id', 'theme_id']);
        });

    }

    public function down(): void
    {
        // drop pivots first if present
        Schema::dropIfExists('feature_slot');
        Schema::dropIfExists('slot_theme');
        Schema::dropIfExists('feature_slot');
        Schema::dropIfExists('slot_theme');
        Schema::dropIfExists('slots');
    }
};
