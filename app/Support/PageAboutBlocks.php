<?php

namespace App\Support;

/**
 * Admin-built description blocks (page_settings.blocks) → white page-about cards for partials/page-about.
 * Stored as a flat Filament Builder list: a `section` element opens a new card, the elements after it fill that card.
 */
class PageAboutBlocks
{
    public const TYPES = [
        'section', 'heading', 'text', 'rule', 'image', 'cards', 'checks', 'steps',
        'tip', 'feature', 'articles', 'guides', 'guides_link',
    ];

    /**
     * @param  mixed  $raw  list of ['type' => ..., 'data' => [...]]
     * @return list<array{title: string, hero: string, pills: list<string>, guides: bool, elements: list<array<string, mixed>>}>
     */
    public static function sections(mixed $raw): array
    {
        $sections = [];
        $current = null;

        foreach (is_array($raw) ? $raw : [] as $block) {
            $type = (string) ($block['type'] ?? '');
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];
            if (! in_array($type, self::TYPES, true)) {
                continue;
            }

            if ($type === 'section') {
                if ($current !== null) {
                    $sections[] = $current;
                }
                $current = [
                    'title' => self::str($data, 'title'),
                    'hero' => self::str($data, 'hero_image'),
                    'pills' => self::lines($data['pills'] ?? ''),
                    'guides' => false,
                    'elements' => [],
                ];

                continue;
            }

            $element = self::element($type, $data);
            if ($element === null) {
                continue;
            }

            $current ??= ['title' => '', 'hero' => '', 'pills' => [], 'guides' => false, 'elements' => []];
            $current['guides'] = $current['guides'] || $type === 'guides_link';
            $current['elements'][] = $element;
        }

        if ($current !== null) {
            $sections[] = $current;
        }

        return array_values(array_filter($sections, fn (array $section): bool => $section['title'] !== '' || $section['hero'] !== '' || $section['elements'] !== []));
    }

    /**
     * Uploaded image paths inside the blocks of every locale (for mirroring R2 → public disk).
     *
     * @return list<string>
     */
    public static function imagePaths(mixed $blocksByLocale): array
    {
        $paths = [];
        array_walk_recursive($blocksByLocale, function ($value, $key) use (&$paths): void {
            if (in_array($key, ['image', 'hero_image'], true) && is_string($value) && $value !== '') {
                $paths[] = $value;
            }
        });

        return array_values(array_unique($paths));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    private static function element(string $type, array $data): ?array
    {
        $element = match ($type) {
            'heading' => ['text' => self::str($data, 'text'), 'level' => ($data['level'] ?? '') === 'h2' ? 'h2' : 'h3'],
            'text' => ['text' => self::str($data, 'text'), 'lead' => (bool) ($data['lead'] ?? false)],
            'rule' => [],
            'image' => ['image' => self::str($data, 'image'), 'alt' => self::str($data, 'alt'), 'style' => ($data['style'] ?? '') === 'promo' ? 'promo' : 'banner'],
            'cards' => [
                'columns' => (int) ($data['columns'] ?? 2) === 3 ? 3 : 2,
                'style' => ($data['style'] ?? '') === 'outline' ? 'outline' : 'dark',
                'cards' => self::items($data['cards'] ?? [], fn (array $card): array => [
                    'title' => self::str($card, 'title'),
                    'text' => self::str($card, 'text'),
                    'items' => self::lines($card['items'] ?? ''),
                ], fn (array $card): bool => $card['title'] !== '' || $card['text'] !== '' || $card['items'] !== []),
            ],
            'checks' => ['items' => self::lines($data['items'] ?? ''), 'compact' => (bool) ($data['compact'] ?? false)],
            'steps' => ['items' => self::lines($data['items'] ?? '')],
            'tip' => ['title' => self::str($data, 'title'), 'text' => self::str($data, 'text')],
            'feature' => ['image' => self::str($data, 'image'), 'title' => self::str($data, 'title'), 'meta' => self::str($data, 'meta'), 'url' => self::str($data, 'url')],
            'articles' => ['items' => self::items($data['items'] ?? [], fn (array $item): array => [
                'image' => self::str($item, 'image'),
                'label' => self::str($item, 'label'),
                'title' => self::str($item, 'title'),
                'meta' => self::str($item, 'meta'),
                'url' => self::str($item, 'url'),
                'button' => self::str($item, 'button'),
            ], fn (array $item): bool => $item['title'] !== '')],
            'guides' => [
                'heading' => self::str($data, 'heading'),
                'text' => self::str($data, 'text'),
                'columns' => (int) ($data['columns'] ?? 3) === 2 ? 2 : 3,
                'cards' => self::items($data['cards'] ?? [], fn (array $card): array => [
                    'image' => self::str($card, 'image'),
                    'title' => self::str($card, 'title'),
                    'url' => self::str($card, 'url'),
                ], fn (array $card): bool => $card['title'] !== ''),
            ],
            'guides_link' => ['label' => self::str($data, 'label'), 'url' => self::str($data, 'url')],
        };

        $filled = match ($type) {
            'heading', 'text' => $element['text'] !== '',
            'rule' => true,
            'image' => $element['image'] !== '',
            'cards' => $element['cards'] !== [],
            'checks', 'steps', 'articles' => $element['items'] !== [],
            'tip' => $element['title'] !== '' || $element['text'] !== '',
            'feature' => $element['title'] !== '',
            'guides' => $element['heading'] !== '' || $element['cards'] !== [],
            'guides_link' => $element['label'] !== '',
        };

        return $filled ? ['type' => $type] + $element : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function str(array $data, string $key): string
    {
        $value = $data[$key] ?? '';

        return is_scalar($value) ? trim((string) $value) : '';
    }

    /**
     * @return list<string>
     */
    private static function lines(mixed $value): array
    {
        $lines = is_array($value) ? $value : preg_split('/\R/', (string) $value);

        return array_values(array_filter(array_map(fn ($line): string => trim((string) $line), $lines), fn (string $line): bool => $line !== ''));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function items(mixed $items, callable $map, callable $keep): array
    {
        $rows = array_map(fn ($item): array => $map(is_array($item) ? $item : []), is_array($items) ? array_values($items) : []);

        return array_values(array_filter($rows, $keep));
    }
}
