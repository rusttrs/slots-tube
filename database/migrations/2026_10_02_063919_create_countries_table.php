<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->char('code', 2)->unique();
            $table->json('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

    }

    public function down(): void
    {
        // drop pivots first if present
        Schema::dropIfExists('feature_slot');
        Schema::dropIfExists('slot_theme');
        Schema::dropIfExists('countries');
    }
};
