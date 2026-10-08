<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_pages', function (Blueprint $table) {
            $table->id();
            $table->string('key', 32)->unique();
            $table->json('faq')->nullable();
            $table->timestamps();
        });

        $now = now();
        DB::table('content_pages')->insert(array_map(
            fn (string $key, array $faq): array => [
                'key' => $key,
                'faq' => json_encode(['en' => $faq, 'de' => [], 'fr' => []], JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            array_keys(self::DEFAULT_FAQ),
            self::DEFAULT_FAQ,
        ));
    }

    private const DEFAULT_FAQ = [
        'hub' => [
            ['question' => 'What can I read on slots.tube?', 'answer' => 'Casino and slots news, step-by-step guides, personal blogs from our authors and stories about slot streamers. Every section is updated by the slots.tube editorial team.'],
            ['question' => 'Who writes the publications?', 'answer' => 'Each article is published under a named author from our team. You can open the author page from any article to see their background and other publications.'],
            ['question' => 'How is Popular News chosen?', 'answer' => 'Popular News shows the publications with the most likes from registered readers. Like an article to help others find it.'],
            ['question' => 'How do I get new articles first?', 'answer' => 'Subscribe to the slots.tube newsletter at the bottom of the page and we will send you the latest news, guides and bonuses.'],
        ],
        'news' => [
            ['question' => 'What kind of news do you cover?', 'answer' => 'New slot releases, provider announcements, casino industry updates, regulation changes and notable big wins.'],
            ['question' => 'How often is the news section updated?', 'answer' => 'We publish news as soon as it is verified, usually several times a week.'],
            ['question' => 'Can I suggest a news story?', 'answer' => 'Yes. Leave a comment under any article or contact the team, and our editors will review your tip.'],
        ],
        'blog' => [
            ['question' => 'How are blogs different from news?', 'answer' => 'Blogs are personal: authors share their own sessions, opinions and experience. News stays factual and neutral.'],
            ['question' => 'Are blog posts gambling advice?', 'answer' => 'No. Blog posts reflect the author’s experience. Always play responsibly and only with money you can afford to lose.'],
            ['question' => 'Can I discuss a blog post?', 'answer' => 'Yes. Sign in to comment and like posts and replies from other readers.'],
        ],
        'guide' => [
            ['question' => 'Who are the guides for?', 'answer' => 'For beginners and experienced players alike: from how RTP and volatility work to bonus buys and bankroll management.'],
            ['question' => 'Are the guides kept up to date?', 'answer' => 'Yes. When rules, features or bonuses change we update the guide and show the update date and author.'],
            ['question' => 'Can I try the strategies for free?', 'answer' => 'Most slots on slots.tube have a free demo, so you can test what you learn without risking real money.'],
        ],
        'streamer' => [
            ['question' => 'Which streamers do you write about?', 'answer' => 'Popular slot streamers on Twitch, Kick and YouTube, their best sessions, records and community events.'],
            ['question' => 'Are streamer wins realistic for regular players?', 'answer' => 'Streams often use large balances and sponsored accounts. Treat big wins as entertainment, not as an expected result.'],
            ['question' => 'Can I watch streams on slots.tube?', 'answer' => 'Streamer articles link to their channels and highlight the best clips, so you can jump straight to the action.'],
        ],
    ];

    public function down(): void
    {
        Schema::dropIfExists('content_pages');
    }
};
