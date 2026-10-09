<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LOCALES = ['en', 'de', 'fr'];

    public function up(): void
    {
        Schema::table('page_settings', function (Blueprint $table) {
            $table->json('sections')->nullable()->after('texts');
        });

        $authors = DB::table('authors')->orderBy('sort_order')->orderBy('id')->get(['id', 'team_group']);
        $page = DB::table('page_settings')->where('key', 'authors')->first();
        $texts = $page && $page->texts ? json_decode($page->texts, true) : [];

        $sections = [];
        foreach (['editors' => true, 'team' => false] as $group => $showJoin) {
            $title = [];
            $text = [];
            foreach (self::LOCALES as $locale) {
                $title[$locale] = trim((string) ($texts[$locale][$group.'_title'] ?? '')) ?: __("author.team_page.{$group}_title", [], $locale);
                $text[$locale] = trim((string) ($texts[$locale][$group.'_text'] ?? '')) ?: __("author.team_page.{$group}_text", [], $locale);
            }

            $sections[] = [
                'title' => $title,
                'text' => $text,
                'author_ids' => $authors
                    ->filter(fn (object $author): bool => ($group === 'team') === ($author->team_group === 'team'))
                    ->pluck('id')
                    ->map(fn ($id): string => (string) $id)
                    ->values()
                    ->all(),
                'show_join' => $showJoin,
            ];
        }

        foreach (self::LOCALES as $locale) {
            unset($texts[$locale]['editors_title'], $texts[$locale]['editors_text'], $texts[$locale]['team_title'], $texts[$locale]['team_text']);
        }

        $values = [
            'sections' => json_encode($sections, JSON_UNESCAPED_UNICODE),
            'texts' => $texts === [] ? null : json_encode($texts, JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ];

        $page
            ? DB::table('page_settings')->where('id', $page->id)->update($values)
            : DB::table('page_settings')->insert($values + ['key' => 'authors', 'created_at' => now()]);

        Schema::table('authors', function (Blueprint $table) {
            $table->dropColumn('team_group');
        });
    }

    public function down(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->string('team_group', 20)->default('editors')->after('sort_order');
        });

        $page = DB::table('page_settings')->where('key', 'authors')->first();
        $sections = $page && $page->sections ? json_decode($page->sections, true) : [];
        $teamIds = array_map('intval', $sections[1]['author_ids'] ?? []);
        if ($teamIds !== []) {
            DB::table('authors')->whereIn('id', $teamIds)->update(['team_group' => 'team']);
        }

        Schema::table('page_settings', function (Blueprint $table) {
            $table->dropColumn('sections');
        });
    }
};
