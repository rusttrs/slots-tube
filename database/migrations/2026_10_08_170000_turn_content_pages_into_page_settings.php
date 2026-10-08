<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const KEYS = [
        'hub' => 'content',
        'news' => 'content.news',
        'blog' => 'content.blogs',
        'guide' => 'content.guides',
        'streamer' => 'content.streamers',
    ];

    public function up(): void
    {
        Schema::rename('content_pages', 'page_settings');

        Schema::table('page_settings', function (Blueprint $table) {
            $table->string('key', 64)->change();
            $table->json('meta_title')->nullable()->after('key');
            $table->json('meta_description')->nullable()->after('meta_title');
            $table->boolean('noindex')->default(false)->after('meta_description');
        });

        foreach (self::KEYS as $old => $new) {
            DB::table('page_settings')->where('key', $old)->update(['key' => $new]);
        }
    }

    public function down(): void
    {
        foreach (self::KEYS as $old => $new) {
            DB::table('page_settings')->where('key', $new)->update(['key' => $old]);
        }

        Schema::table('page_settings', function (Blueprint $table) {
            $table->dropColumn(['meta_title', 'meta_description', 'noindex']);
        });

        Schema::rename('page_settings', 'content_pages');
    }
};
