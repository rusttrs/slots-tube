<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->json('position')->nullable()->after('role');
            $table->json('favorites_title')->nullable()->after('favorite_post_ids');
            $table->boolean('show_latest_slots')->default(true)->after('favorites_title');
            $table->json('latest_slots_title')->nullable()->after('show_latest_slots');
            $table->json('latest_slot_ids')->nullable()->after('latest_slots_title');
            $table->boolean('show_latest_posts')->default(true)->after('latest_slot_ids');
            $table->json('latest_posts_title')->nullable()->after('show_latest_posts');
            $table->json('latest_post_ids')->nullable()->after('latest_posts_title');
            $table->boolean('noindex')->default(false)->after('meta_description');
            $table->json('social_links')->nullable()->after('noindex');
        });

        DB::table('authors')
            ->whereNotIn('role', ['author', 'staff', ''])
            ->orderBy('id')
            ->each(function (object $author): void {
                $role = trim((string) $author->role);
                DB::table('authors')->where('id', $author->id)->update([
                    'position' => json_encode(['en' => $role, 'de' => $role, 'fr' => $role], JSON_UNESCAPED_UNICODE),
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->dropColumn([
                'position', 'favorites_title',
                'show_latest_slots', 'latest_slots_title', 'latest_slot_ids',
                'show_latest_posts', 'latest_posts_title', 'latest_post_ids',
                'noindex', 'social_links',
            ]);
        });
    }
};
