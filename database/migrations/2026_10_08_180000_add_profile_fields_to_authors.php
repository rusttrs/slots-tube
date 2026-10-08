<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->json('page_title')->nullable()->after('name');
            $table->json('meta_title')->nullable()->after('sort_order');
            $table->json('meta_description')->nullable()->after('meta_title');
            $table->json('favorite_slot_ids')->nullable()->after('traits');
            $table->json('red_flag_slot_ids')->nullable()->after('favorite_slot_ids');
            $table->json('top_streamers')->nullable()->after('red_flag_slot_ids');
            $table->json('favorite_post_ids')->nullable()->after('top_streamers');
        });
    }

    public function down(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->dropColumn([
                'page_title', 'meta_title', 'meta_description',
                'favorite_slot_ids', 'red_flag_slot_ids', 'top_streamers', 'favorite_post_ids',
            ]);
        });
    }
};
