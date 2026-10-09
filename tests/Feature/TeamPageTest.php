<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\PageSetting;
use Tests\TestCase;

class TeamPageTest extends TestCase
{
    public function test_team_page_is_registered_with_faq_texts_and_groups(): void
    {
        $page = new PageSetting(['key' => 'authors']);

        $this->assertTrue($page->hasFaq());
        $this->assertTrue($page->hasTeamSections());
        $this->assertSame('/authors/', $page->path());
        $this->assertArrayHasKey('join_label', $page->textFields());
        $this->assertSame(url('/authors'), route('authors'));
        $this->assertFalse((new PageSetting(['key' => 'content']))->hasTeamSections());
    }

    public function test_page_texts_fall_back_to_english_then_defaults(): void
    {
        $page = new PageSetting(['key' => 'authors', 'texts' => [
            'en' => ['title' => 'Meet the Team', 'lead' => ' '],
            'de' => ['title' => 'Unser Team'],
        ]]);

        $this->assertSame('Unser Team', $page->text('title', 'de'));
        $this->assertSame('Meet the Team', $page->text('title', 'fr'));
        $this->assertSame('Slots Tube Team Members', (new PageSetting(['key' => 'authors']))->text('title', 'en'));
        $this->assertSame(__('author.team_page.lead', [], 'en'), $page->text('lead', 'en'));
        $this->assertSame('', $page->text('join_url', 'en'));
        $this->assertSame('', $page->text('unknown', 'en'));
    }

    public function test_team_sections_are_normalized(): void
    {
        $page = new PageSetting(['key' => 'authors', 'sections' => [
            ['title' => ['en' => 'Our Editors'], 'author_ids' => ['3', '1', '3', '', null], 'show_join' => '1'],
            'broken',
            ['title' => ['en' => 'Other Team Members']],
        ]]);

        $this->assertSame([
            ['title' => ['en' => 'Our Editors'], 'text' => [], 'author_ids' => [3, 1], 'show_join' => true],
            ['title' => ['en' => 'Other Team Members'], 'text' => [], 'author_ids' => [], 'show_join' => false],
        ], $page->teamSections());
    }

    public function test_author_lists_the_groups_it_belongs_to(): void
    {
        $page = new PageSetting(['key' => 'authors', 'sections' => [
            ['title' => ['en' => 'Our Editors'], 'author_ids' => ['1', '2']],
            ['title' => ['en' => 'Other Team Members'], 'author_ids' => ['3']],
        ]]);

        $author = new Author;
        $author->id = 3;
        $this->assertSame(['Other Team Members'], $author->teamSectionTitles($page));

        $author->id = 9;
        $this->assertSame([], $author->teamSectionTitles($page));
    }
}
