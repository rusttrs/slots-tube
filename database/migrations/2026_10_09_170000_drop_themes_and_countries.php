<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('slot_theme');
        Schema::dropIfExists('themes');
        Schema::dropIfExists('countries');
    }

    public function down(): void
    {
        if (! Schema::hasTable('themes')) {
            Schema::create('themes', function (Blueprint $table) {
                $table->id();
                $table->json('name');
                $table->string('slug')->unique();
                $table->json('description')->nullable();
                $table->boolean('is_published')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('slot_theme')) {
            Schema::create('slot_theme', function (Blueprint $table) {
                $table->foreignId('slot_id')->constrained()->cascadeOnDelete();
                $table->foreignId('theme_id')->constrained()->cascadeOnDelete();
                $table->primary(['slot_id', 'theme_id']);
            });
        }

        if (! Schema::hasTable('countries')) {
            Schema::create('countries', function (Blueprint $table) {
                $table->id();
                $table->char('code', 2)->unique();
                $table->json('name');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }
};
