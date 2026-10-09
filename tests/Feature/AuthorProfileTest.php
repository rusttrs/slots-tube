<?php

namespace Tests\Feature;

use App\Models\Author;
use Tests\TestCase;

class AuthorProfileTest extends TestCase
{
    public function test_public_url_is_under_authors(): void
    {
        $author = new Author(['slug' => 'dmitriy-collins']);

        $this->assertSame(url('/authors/dmitriy-collins').'/', $author->publicUrl('en'));
        $this->assertSame(url('/de/authors/dmitriy-collins').'/', $author->publicUrl('de'));
        $this->assertSame(url('/authors/dmitriy-collins'), route('authors.show', 'dmitriy-collins'));
    }

    public function test_position_prefers_translation_then_legacy_role(): void
    {
        $author = new Author(['role' => 'Senior Slot Analyst']);
        $this->assertSame('Senior Slot Analyst', $author->positionLabel('de'));

        $author->role = 'staff';
        $this->assertNull($author->positionLabel('en'));

        $author->setTranslations('position', ['en' => 'Project Manager', 'de' => 'Projektleiter']);
        $this->assertSame('Projektleiter', $author->positionLabel('de'));
        $this->assertSame('Project Manager', $author->positionLabel('fr'));
    }

    public function test_titles_fall_back_to_defaults_with_name(): void
    {
        $author = new Author;
        $author->setTranslations('name', ['en' => 'Dmitriy Collins']);

        $this->assertSame('Dmitriy Collins – About the Author', $author->pageTitle('en'));
        $this->assertSame('Dmitriy Collins Latest Slots', $author->latestSlotsTitle('en'));
        $this->assertSame('Neueste Beiträge von Dmitriy Collins', $author->latestPostsTitle('de'));
        $this->assertSame('Favorite Slots & Red Flags Slots', $author->favoritesTitle('en'));
        $this->assertSame('Dmitriy Collins – About the Author | slots.tube', $author->metaTitle('en'));

        $author->setTranslations('page_title', ['en' => 'Dmitriy Collins – About Dmitriy and His Position']);
        $author->setTranslations('latest_slots_title', ['en' => 'Slots Dmitriy reviewed']);
        $this->assertSame('Dmitriy Collins – About Dmitriy and His Position', $author->pageTitle('fr'));
        $this->assertSame('Slots Dmitriy reviewed', $author->latestSlotsTitle('en'));
    }

    public function test_tags_fall_back_to_english_and_drop_blanks(): void
    {
        $author = new Author;
        $author->setTranslations('traits', ['en' => ['Project Visionary', ' ', 'Scam Investigator']]);

        $this->assertSame(['Project Visionary', 'Scam Investigator'], $author->tags('de'));
    }

    public function test_same_as_keeps_only_http_links(): void
    {
        $author = new Author(['social_links' => [
            ['network' => 'linkedin', 'url' => 'https://linkedin.com/in/dmitriy'],
            ['network' => 'x', 'url' => 'javascript:alert(1)'],
            ['network' => 'kick', 'url' => 'https://linkedin.com/in/dmitriy'],
        ]]);

        $this->assertSame(['https://linkedin.com/in/dmitriy'], $author->sameAsUrls());
    }

    public function test_hidden_latest_blocks_are_empty(): void
    {
        $author = new Author(['show_latest_slots' => false, 'show_latest_posts' => false]);

        $this->assertTrue($author->latestSlots()->isEmpty());
        $this->assertTrue($author->latestPosts()->isEmpty());
    }

    public function test_top_streamers_skip_rows_without_name(): void
    {
        $author = new Author(['top_streamers' => [
            ['name' => 'Roshtein', 'url' => 'https://kick.com/roshtein'],
            ['name' => '', 'url' => 'https://kick.com/x'],
            ['name' => 'Xposed', 'url' => ''],
        ]]);

        $this->assertSame([
            ['name' => 'Roshtein', 'url' => 'https://kick.com/roshtein'],
            ['name' => 'Xposed', 'url' => null],
        ], $author->topStreamers());
    }
}
