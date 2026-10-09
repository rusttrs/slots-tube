<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Provider;
use App\Models\Slot;
use App\Support\SlotSchema;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SlotSchemaTest extends TestCase
{
    private const CANONICAL = 'https://slots.test/slots/gates-of-olympus/';

    private function slot(array $overrides = []): Slot
    {
        $slot = new Slot(array_merge([
            'title' => ['en' => 'Gates of Olympus'],
            'slug' => 'gates-of-olympus',
            'intro' => ['en' => 'Pragmatic Play slot with 96.50% default RTP.'],
            'cover_path' => 'https://cdn.slots.test/covers/goo.png',
            'demo_url' => 'https://demo.slots.test/goo',
            'game_type' => 'Video Slot',
            'technology' => 'JS, HTML5',
            'theme_text' => 'Ancient Greece, Zeus',
            'release_date' => '2021-02-01',
            'editorial_score' => 4.4,
            'review_overview' => ['en' => '<p>High-volatility 6×5 slot.</p><p>Pay Anywhere wins.</p>'],
            'pros' => ['en' => "Clear tumbles\nVisible multipliers"],
            'cons' => ['en' => 'High volatility'],
            'screenshots' => [['path' => 'https://cdn.slots.test/shots/base.png', 'caption' => ['en' => 'Base game']]],
            'faq' => ['en' => [
                ['question' => 'What is the RTP?', 'answer' => 'Up to 96.50% </script><script>alert(1)</script>'],
                ['question' => '', 'answer' => 'skipped'],
            ]],
        ], $overrides));
        $slot->created_at = Carbon::parse('2026-10-03 10:00:00');

        $slot->setRelation('provider', new Provider([
            'name' => ['en' => 'Pragmatic Play'],
            'slug' => 'pragmatic-play',
            'is_published' => true,
        ]));
        $slot->setRelation('author', new Author([
            'name' => ['en' => 'Marcus Hale'],
            'slug' => 'marcus-hale',
            'is_published' => true,
        ]));
        $slot->setRelation('reviewer', null);
        $slot->setRelation('updatedByAuthor', null);

        return $slot;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function nodes(array $graph): array
    {
        return collect($graph['@graph'])->keyBy('@type')->all();
    }

    public function test_graph_describes_review_game_and_faq(): void
    {
        $nodes = $this->nodes(SlotSchema::graph($this->slot(), self::CANONICAL, 4.2, 15));

        $this->assertSame(
            ['Organization', 'WebSite', 'WebPage', 'BreadcrumbList', 'Review', 'VideoGame', 'FAQPage'],
            array_keys($nodes)
        );

        $review = $nodes['Review'];
        $this->assertSame(['@id' => self::CANONICAL.'#game'], $review['itemReviewed']);
        $this->assertSame(4.4, $review['reviewRating']['ratingValue']);
        $this->assertSame('High-volatility 6×5 slot. Pay Anywhere wins.', $review['reviewBody']);
        $this->assertSame(url('/authors/marcus-hale').'/#person', $review['author']['@id']);
        $this->assertCount(2, $review['positiveNotes']['itemListElement']);
        $this->assertSame('2026-10-03T10:00:00+00:00', $review['datePublished']);

        $game = $nodes['VideoGame'];
        $this->assertSame('Gates of Olympus', $game['name']);
        $this->assertSame('2021-02', $game['datePublished']);
        $this->assertSame('Pragmatic Play', $game['author']['name']);
        $this->assertSame(url('/providers/pragmatic-play').'/', $game['author']['url']);
        $this->assertSame(4.2, $game['aggregateRating']['ratingValue']);
        $this->assertSame(15, $game['aggregateRating']['ratingCount']);
        $this->assertSame(0, $game['offers']['price']);
        $this->assertSame('Web browser', $game['gamePlatform']);
        $this->assertSame('https://cdn.slots.test/shots/base.png', $game['screenshot'][0]['url']);

        $this->assertCount(1, $nodes['FAQPage']['mainEntity']);
        $this->assertSame('What is the RTP?', $nodes['FAQPage']['mainEntity'][0]['name']);
    }

    public function test_optional_nodes_are_omitted_without_data(): void
    {
        $nodes = $this->nodes(SlotSchema::graph(
            $this->slot(['faq' => null, 'demo_url' => null, 'editorial_score' => null]),
            self::CANONICAL,
            null,
            0
        ));

        $this->assertArrayNotHasKey('FAQPage', $nodes);
        $this->assertArrayNotHasKey('aggregateRating', $nodes['VideoGame']);
        $this->assertArrayNotHasKey('offers', $nodes['VideoGame']);
        $this->assertArrayNotHasKey('reviewRating', $nodes['Review']);
    }

    public function test_json_cannot_break_out_of_script_tag(): void
    {
        $json = SlotSchema::json($this->slot(), self::CANONICAL, 4.2, 15);

        $this->assertStringNotContainsString('</script>', $json);
        $this->assertStringNotContainsString('<script>', $json);
        $this->assertNotNull(json_decode($json, true));
    }
}
