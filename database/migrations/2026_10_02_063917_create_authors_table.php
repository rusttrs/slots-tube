<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('slug')->unique();
            $table->string('role')->default('author'); // author|staff
            $table->string('avatar_path')->nullable();
            $table->json('bio')->nullable();
            $table->json('traits')->nullable();
            $table->date('started_at')->nullable();
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
        Schema::dropIfExists('authors');
    }
};
