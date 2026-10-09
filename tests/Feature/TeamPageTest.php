<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\PageSetting;
use Tests\TestCase;

class TeamPageTest extends TestCase
{
    public function test_team_page_is_registered_with_faq_and_texts(): void
    {
        $page = new PageSetting(['key' => 'authors']);

        $this->assertTrue($page->hasFaq());
        $this->assertSame('/authors/', $page->path());
        $this->assertArrayHasKey('editors_title', $page->textFields());
        $this->assertSame(url('/authors'), route('authors'));
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

    public function test_unknown_team_group_counts_as_editors(): void
    {
        $this->assertSame('editors', (new Author)->teamGroup());
        $this->assertSame('editors', (new Author(['team_group' => 'bogus']))->teamGroup());
        $this->assertSame('team', (new Author(['team_group' => 'team']))->teamGroup());
    }
}
