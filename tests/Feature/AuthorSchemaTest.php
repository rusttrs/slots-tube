<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Post;
use App\Models\Slot;
use App\Support\AuthorSchema;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AuthorSchemaTest extends TestCase
{
    private function makeAuthor(string $slug = 'dmitriy-collins', string $name = 'Dmitriy Collins'): Author
    {
        $author = new Author([
            'slug' => $slug,
            'name' => ['en' => $name],
            'position' => ['en' => 'Project Manager'],
            'traits' => ['en' => ['Project Visionary', 'Scam Investigator']],
            'started_at' => '2021-11-15',
            'is_published' => true,
            'social_links' => [['network' => 'linkedin', 'url' => 'https://linkedin.com/in/dmitriy']],
        ]);
        $author->created_at = Carbon::parse('2026-10-01 09:00:00', 'UTC');
        $author->updated_at = Carbon::parse('2026-10-09 12:00:00', 'UTC');

        return $author;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function nodes(array $schema): array
    {
        return collect($schema['@graph'])->keyBy(fn (array $node): string => $node['@id'] ?? implode(',', (array) $node['@type']))->all();
    }

    public function test_profile_describes_person_with_role_works_and_lists(): void
    {
        $canonical = url('/authors/dmitriy-collins').'/';
        $slots = new Collection([new Slot(['slug' => 'sugar-rush', 'title' => ['en' => 'Sugar Rush']])]);
        $posts = new Collection([new Post(['type' => 'news', 'slug' => 'big-wins', 'title' => ['en' => 'Big wins']])]);

        $nodes = $this->nodes(AuthorSchema::profile($this->makeAuthor(), $canonical, $slots, $posts, 7, 'en'));
        $site = rtrim(config('app.url'), '/');

        $page = $nodes[$canonical.'#webpage'];
        $this->assertSame('ProfilePage', $page['@type']);
        $this->assertSame(['@id' => $canonical.'#person'], $page['mainEntity']);
        $this->assertSame('2026-10-09T12:00:00+00:00', $page['dateModified']);
        $this->assertSame([['@id' => $canonical.'#latest-slots'], ['@id' => $canonical.'#latest-posts']], $page['hasPart']);

        $person = $nodes[$canonical.'#person'];
        $this->assertSame('Dmitriy Collins', $person['name']);
        $this->assertSame('Project Manager', $person['jobTitle']);
        $this->assertSame(['Project Visionary', 'Scam Investigator'], $person['knowsAbout']);
        $this->assertSame(['https://linkedin.com/in/dmitriy'], $person['sameAs']);
        $this->assertSame('2021-11-15', $person['worksFor']['startDate']);
        $this->assertSame(['@id' => $site.'/#organization'], $person['worksFor']['worksFor']);
        $this->assertSame(7, $person['agentInteractionStatistic']['userInteractionCount']);

        $this->assertSame(url('/slots/sugar-rush').'/', $nodes[$canonical.'#latest-slots']['itemListElement'][0]['url']);
        $this->assertSame('Big wins', $nodes[$canonical.'#latest-posts']['itemListElement'][0]['name']);

        $crumbs = $nodes[$canonical.'#breadcrumb']['itemListElement'];
        $this->assertSame(['Home', 'Our Team', 'Dmitriy Collins – About the Author'], array_column($crumbs, 'name'));
        $this->assertSame(url('/authors').'/', $crumbs[1]['item']);
        $this->assertArrayHasKey($site.'/#organization', $nodes);
        $this->assertArrayHasKey($site.'/#website', $nodes);
    }

    public function test_profile_skips_empty_blocks_and_counter(): void
    {
        $canonical = url('/authors/dmitriy-collins').'/';
        $nodes = $this->nodes(AuthorSchema::profile($this->makeAuthor(), $canonical, new Collection, new Collection, 0, 'en'));

        $this->assertArrayNotHasKey('hasPart', $nodes[$canonical.'#webpage']);
        $this->assertArrayNotHasKey('agentInteractionStatistic', $nodes[$canonical.'#person']);
        $this->assertArrayNotHasKey($canonical.'#latest-slots', $nodes);
    }

    public function test_team_lists_people_across_groups_with_profile_ids(): void
    {
        $canonical = url('/de/authors').'/';
        $sections = [
            ['title' => 'Our Editors', 'text' => '', 'authors' => new Collection([$this->makeAuthor()]), 'show_join' => true],
            ['title' => 'Other', 'text' => '', 'authors' => new Collection([$this->makeAuthor('sonja-collins', 'Sonja Collins')]), 'show_join' => false],
        ];

        $nodes = $this->nodes(AuthorSchema::team($canonical, 'Das Team', 'Unsere Redaktion.', $sections, 'de'));

        $page = $nodes[$canonical.'#webpage'];
        $this->assertSame(['AboutPage', 'CollectionPage'], $page['@type']);
        $this->assertSame('de', $page['inLanguage']);

        $items = $nodes[$canonical.'#itemlist']['itemListElement'];
        $this->assertSame(2, $nodes[$canonical.'#itemlist']['numberOfItems']);
        $this->assertSame([1, 2], array_column($items, 'position'));
        $this->assertSame(url('/de/authors/sonja-collins').'/#person', $items[1]['item']['@id']);
        $this->assertSame('Project Manager', $items[0]['item']['jobTitle']);
        $this->assertSame(['Startseite', 'Unser Team'], array_column($nodes[$canonical.'#breadcrumb']['itemListElement'], 'name'));
    }

    public function test_team_without_authors_has_no_item_list(): void
    {
        $canonical = url('/authors').'/';
        $nodes = $this->nodes(AuthorSchema::team($canonical, 'Team', '', [], 'en'));

        $this->assertArrayNotHasKey('mainEntity', $nodes[$canonical.'#webpage']);
        $this->assertArrayNotHasKey($canonical.'#itemlist', $nodes);
    }
}
