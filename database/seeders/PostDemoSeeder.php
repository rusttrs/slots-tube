<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Post;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PostDemoSeeder extends Seeder
{
    public function run(): void
    {
        $marcus = Author::query()->where('slug', 'marcus-hale')->value('id')
            ?: Author::query()->value('id');
        $elena = Author::query()->where('slug', 'elena-voss')->value('id')
            ?: $marcus;

        $kuminaboy = Author::query()->updateOrCreate(
            ['slug' => 'kuminaboy'],
            [
                'role' => 'Community',
                'avatar_path' => 'assets/images/avatars/user-01.jpg',
                'is_published' => true,
                'sort_order' => 50,
                'name' => [
                    'en' => 'kuminaboy',
                    'de' => 'kuminaboy',
                    'fr' => 'kuminaboy',
                ],
            ]
        );

        $bodyEn = <<<'HTML'
<p>Colt Lightning is a high volatility slot created by the provider Play'n Go, the RTP of the slot can be from 80 to 96.6 percent. We recommend playing with the highest possible RTP. Play'n Go hides RTP slots from players, in the Colt Lightning game settings you will not find information about RTP.</p>
<h2>Explosive, Fun and Tiki!</h2>
<p>TikiPop is the latest addition to the YG Masters portfolio, signed by Yggdrasil, in partnership with AvatarUX. The game has been released following the recent Labyrinth of Knossos MultiJump launch and using Yggdrasil’s innovative GATI technology.</p>
<figure><img src="assets/images/posts/seed/topic-body.jpg" alt="Jackpot cash © Unsplash - ben frost" data-id="assets/images/posts/seed/topic-body.jpg"><figcaption>Jackpot cash © Unsplash - ben frost</figcaption></figure>
<p>Colt Lightning is a high volatility slot created by the provider Play'n Go, the RTP of the slot can be from 80 to 96.6 percent. We recommend playing with the highest possible RTP. Play'n Go hides RTP slots from players, in the Colt Lightning game settings you will not find information about RTP.</p>
HTML;

        $bodyDe = <<<'HTML'
<p>Colt Lightning ist ein Slot mit hoher Volatilität von Play'n Go. Der RTP kann zwischen 80 und 96,6 Prozent liegen. Wir empfehlen, mit dem höchstmöglichen RTP zu spielen.</p>
<h2>Explosive, Fun and Tiki!</h2>
<p>TikiPop ist der neueste Titel im YG Masters Portfolio von Yggdrasil in Partnerschaft mit AvatarUX.</p>
<figure><img src="assets/images/posts/seed/topic-body.jpg" alt="Jackpot cash © Unsplash - ben frost" data-id="assets/images/posts/seed/topic-body.jpg"><figcaption>Jackpot cash © Unsplash - ben frost</figcaption></figure>
<p>Im Colt Lightning Menü findest du oft keine klare RTP-Angabe — deshalb lohnt sich der Blick in die Provider-Docs.</p>
HTML;

        $bodyFr = <<<'HTML'
<p>Colt Lightning est une machine à sous à haute volatilité de Play'n Go. Le RTP peut aller de 80 à 96,6 %. Nous conseillons de jouer avec le RTP le plus élevé possible.</p>
<h2>Explosive, Fun and Tiki!</h2>
<p>TikiPop est la dernière addition au portefeuille YG Masters, signée Yggdrasil avec AvatarUX.</p>
<figure><img src="assets/images/posts/seed/topic-body.jpg" alt="Jackpot cash © Unsplash - ben frost" data-id="assets/images/posts/seed/topic-body.jpg"><figcaption>Jackpot cash © Unsplash - ben frost</figcaption></figure>
<p>Les réglages du jeu ne montrent pas toujours le RTP — vérifiez les infos du fournisseur avant de lancer les tours.</p>
HTML;

        $items = [
            // news
            [
                'type' => 'news',
                'slug' => 'top-10-slots-by-pragmatic-play',
                'cover' => 'assets/images/posts/seed/cover-01.jpg',
                'author_id' => $marcus,
                'title' => [
                    'en' => 'Top 10 slots by Pragmatic Play right now',
                    'de' => 'Top 10 Slots von Pragmatic Play gerade jetzt',
                    'fr' => 'Top 10 des slots Pragmatic Play du moment',
                ],
                'excerpt' => [
                    'en' => 'A quick news roundup of the hottest Pragmatic Play titles players are spinning this week.',
                    'de' => 'News-Überblick: die heißesten Pragmatic-Play-Titel dieser Woche.',
                    'fr' => 'Tour d’horizon des titres Pragmatic Play les plus joués cette semaine.',
                ],
            ],
            [
                'type' => 'news',
                'slug' => 'new-slot-releases-this-week',
                'cover' => 'assets/images/posts/seed/cover-03.jpg',
                'author_id' => $elena,
                'title' => [
                    'en' => 'New slot releases this week',
                    'de' => 'Neue Slot-Releases dieser Woche',
                    'fr' => 'Nouvelles sorties de slots cette semaine',
                ],
                'excerpt' => [
                    'en' => 'Fresh launches, demos and first impressions from the slots.tube desk.',
                    'de' => 'Frische Launches, Demos und erste Eindrücke vom slots.tube Desk.',
                    'fr' => 'Nouveautés, démos et premières impressions du bureau slots.tube.',
                ],
            ],
            [
                'type' => 'news',
                'slug' => 'casino-provider-updates-october',
                'cover' => 'assets/images/posts/seed/cover-05.jpg',
                'author_id' => $marcus,
                'title' => [
                    'en' => 'Casino provider updates — October',
                    'de' => 'Casino-Provider Updates — Oktober',
                    'fr' => 'Mises à jour des providers — octobre',
                ],
                'excerpt' => [
                    'en' => 'What studios shipped, patched and highlighted across the industry this month.',
                    'de' => 'Was Studios diesen Monat veröffentlicht, gepatcht und hervorgehoben haben.',
                    'fr' => 'Ce que les studios ont publié, corrigé et mis en avant ce mois-ci.',
                ],
            ],
            // blog
            [
                'type' => 'blog',
                'slug' => 'my-first-big-win-story',
                'cover' => 'assets/images/posts/seed/cover-04.jpg',
                'author_id' => $elena,
                'title' => [
                    'en' => 'My first big win story',
                    'de' => 'Meine erste Big-Win-Story',
                    'fr' => 'L’histoire de mon premier gros gain',
                ],
                'excerpt' => [
                    'en' => 'A personal blog about the session that changed how I bankroll high volatility slots.',
                    'de' => 'Persönlicher Blog über die Session, die mein Bankroll-Spiel verändert hat.',
                    'fr' => 'Un billet perso sur la session qui a changé ma façon de gérer la bankroll.',
                ],
            ],
            [
                'type' => 'blog',
                'slug' => 'why-i-play-high-volatility',
                'cover' => 'assets/images/posts/seed/cover-06.jpg',
                'author_id' => $marcus,
                'title' => [
                    'en' => 'Why I play high volatility',
                    'de' => 'Warum ich High Volatility spiele',
                    'fr' => 'Pourquoi je joue en haute volatilité',
                ],
                'excerpt' => [
                    'en' => 'Thoughts on patience, variance and chasing memorable max-win moments.',
                    'de' => 'Gedanken zu Geduld, Varianz und unvergesslichen Max-Win-Momenten.',
                    'fr' => 'Réflexions sur la patience, la variance et les max wins mémorables.',
                ],
            ],
            [
                'type' => 'blog',
                'slug' => 'weekend-session-recap',
                'cover' => 'assets/images/posts/seed/cover-08.jpg',
                'author_id' => $elena,
                'title' => [
                    'en' => 'Weekend session recap',
                    'de' => 'Wochenend-Session Recap',
                    'fr' => 'Récap de ma session du week-end',
                ],
                'excerpt' => [
                    'en' => 'What hit, what missed, and which demos are staying on my playlist.',
                    'de' => 'Was getroffen hat, was nicht — und welche Demos auf der Playlist bleiben.',
                    'fr' => 'Ce qui a payé, ce qui a raté, et quelles démos restent dans ma playlist.',
                ],
            ],
            // guide
            [
                'type' => 'guide',
                'slug' => 'how-to-play-roulette-daily-bonus',
                'cover' => 'assets/images/posts/seed/topic-hero.jpg',
                'author_id' => $kuminaboy->id,
                'content_updated_on' => null,
                'title' => [
                    'en' => 'How to play in our roulette for daily bonus?',
                    'de' => 'So spielst du in unserer Roulette für den Daily Bonus',
                    'fr' => 'Comment jouer à notre roulette pour le bonus quotidien ?',
                ],
                'excerpt' => [
                    'en' => 'A practical guide to roulette daily bonuses, RTP tips and how our community plays for free spins.',
                    'de' => 'Praxis-Guide zu Daily Bonuses in der Roulette, RTP-Tipps und Free Spins in der Community.',
                    'fr' => 'Guide pratique des bonus quotidiens à la roulette, conseils RTP et free spins de la communauté.',
                ],
            ],
            [
                'type' => 'guide',
                'slug' => 'how-to-read-slot-rtp',
                'cover' => 'assets/images/posts/seed/cover-07.jpg',
                'author_id' => $elena,
                'title' => [
                    'en' => 'How to read slot RTP',
                    'de' => 'So liest du den Slot-RTP',
                    'fr' => 'Comment lire le RTP d’un slot',
                ],
                'excerpt' => [
                    'en' => 'A simple guide to RTP ranges, hidden settings and what actually matters before you spin.',
                    'de' => 'Einfacher Guide zu RTP-Ranges, versteckten Settings und dem, was wirklich zählt.',
                    'fr' => 'Guide simple sur les plages de RTP, réglages cachés et ce qui compte vraiment.',
                ],
            ],
            [
                'type' => 'guide',
                'slug' => 'beginners-guide-to-bonus-buys',
                'cover' => 'assets/images/posts/seed/cover-09.jpg',
                'author_id' => $marcus,
                'title' => [
                    'en' => 'Beginners guide to bonus buys',
                    'de' => 'Anfänger-Guide zu Bonus Buys',
                    'fr' => 'Guide débutant des bonus buys',
                ],
                'excerpt' => [
                    'en' => 'When a feature buy makes sense, how to size bets, and common mistakes to avoid.',
                    'de' => 'Wann Feature Buys Sinn machen, wie du Einsätze dimensionierst und typische Fehler vermeidest.',
                    'fr' => 'Quand un feature buy a du sens, comment calibrer la mise et quelles erreurs éviter.',
                ],
            ],
            // streamer
            [
                'type' => 'streamer',
                'slug' => 'best-twitch-slot-streamers',
                'cover' => 'assets/images/posts/seed/cover-10.jpg',
                'author_id' => $elena,
                'title' => [
                    'en' => 'Best Twitch slot streamers to watch',
                    'de' => 'Die besten Twitch Slot-Streamer',
                    'fr' => 'Meilleurs streamers slots sur Twitch',
                ],
                'excerpt' => [
                    'en' => 'Creators who mix entertainment, bankroll honesty and great slot picks.',
                    'de' => 'Creator mit Entertainment, ehrlichem Bankroll-Talk und starken Slot-Picks.',
                    'fr' => 'Créateurs qui mêlent show, bankroll honnête et bons choix de slots.',
                ],
            ],
            [
                'type' => 'streamer',
                'slug' => 'how-streamers-hunt-max-wins',
                'cover' => 'assets/images/posts/seed/cover-11.jpg',
                'author_id' => $marcus,
                'title' => [
                    'en' => 'How streamers hunt max wins',
                    'de' => 'So jagen Streamer Max Wins',
                    'fr' => 'Comment les streamers chassent les max wins',
                ],
                'excerpt' => [
                    'en' => 'Session structure, game selection and community rituals behind viral hit clips.',
                    'de' => 'Session-Struktur, Game Selection und Community-Rituale hinter viralen Hit-Clips.',
                    'fr' => 'Structure de session, choix des jeux et rituels community derrière les clips viraux.',
                ],
            ],
            [
                'type' => 'streamer',
                'slug' => 'community-watch-party-recap',
                'cover' => 'assets/images/posts/seed/cover-12.jpg',
                'author_id' => $elena,
                'title' => [
                    'en' => 'Community watch party recap',
                    'de' => 'Recap der Community Watch Party',
                    'fr' => 'Récap de la watch party community',
                ],
                'excerpt' => [
                    'en' => 'Highlights from our latest live watch party with chat calls and shared spins.',
                    'de' => 'Highlights der letzten Live Watch Party mit Chat-Calls und Shared Spins.',
                    'fr' => 'Temps forts de notre dernière watch party live avec le chat et des spins partagés.',
                ],
            ],
        ];

        $now = Carbon::now();

        foreach ($items as $index => $item) {
            $figure = $item['slug'] === 'how-to-play-roulette-daily-bonus'
                ? 'assets/images/posts/seed/topic-body.jpg'
                : $item['cover'];
            $bodyFor = static function (string $html) use ($figure): string {
                return str_replace('assets/images/posts/seed/topic-body.jpg', $figure, $html);
            };

            $publishedAt = $item['slug'] === 'how-to-play-roulette-daily-bonus'
                ? $now->copy()->subDays(7)
                : $now->copy()->subHours(3 + $index);

            Post::query()->updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'author_id' => $item['author_id'],
                    'type' => $item['type'],
                    'cover_path' => $item['cover'],
                    'title' => $item['title'],
                    'excerpt' => $item['excerpt'],
                    'body' => [
                        'en' => $bodyFor($bodyEn),
                        'de' => $bodyFor($bodyDe),
                        'fr' => $bodyFor($bodyFr),
                    ],
                    'is_published' => true,
                    'is_featured' => $index < 5,
                    'published_at' => $publishedAt,
                    'content_updated_on' => array_key_exists('content_updated_on', $item)
                        ? $item['content_updated_on']
                        : $now->toDateString(),
                    'updated_by_author_id' => $item['author_id'] === $elena ? $marcus : $elena,
                ]
            );

            if ($item['slug'] === 'how-to-play-roulette-daily-bonus') {
                Post::query()->where('slug', $item['slug'])->update([
                    'created_at' => $publishedAt,
                ]);
            }
        }
    }
}
