<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('page_blocks');
        Schema::dropIfExists('static_pages');
    }

    public function down(): void
    {
        if (! Schema::hasTable('static_pages')) {
            Schema::create('static_pages', function (Blueprint $table) {
                $table->id();
                $table->json('title');
                $table->string('slug')->unique();
                $table->json('body')->nullable();
                $table->boolean('is_published')->default(false);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('page_blocks')) {
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
                $table->softDeletes();
            });
        }
    }
};
