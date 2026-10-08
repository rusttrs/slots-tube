<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;

/**
 * SVG-спрайт иконок публичного сайта.
 * Исходники — resources/icons/<имя>.svg, сборка — `php artisan icons:build`
 * в public/assets/icons/sprite.svg (файл коммитится: на сервере сборки нет).
 * В шаблонах — <x-site-icon name="<имя>" width=".." height=".." />.
 */
class IconSprite
{
    public const SPRITE_PATH = 'assets/icons/sprite.svg';

    private const SVG_NS = 'http://www.w3.org/2000/svg';

    /** Атрибуты корневого <svg>, которые не переносятся на <symbol>. */
    private const DROPPED_ROOT_ATTRIBUTES = [
        'id', 'width', 'height', 'x', 'y', 'style', 'class', 'version', 'overflow',
        'role', 'aria-hidden', 'aria-label', 'focusable',
    ];

    private static ?string $version = null;

    public static function url(string $name): string
    {
        return '/'.self::SPRITE_PATH.'?v='.self::version().'#'.$name;
    }

    public static function version(): string
    {
        return self::$version ??= substr((string) @hash_file('xxh128', public_path(self::SPRITE_PATH)), 0, 10);
    }

    public static function sourceDir(): string
    {
        return resource_path('icons');
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        $names = array_map(fn (string $file): string => basename($file, '.svg'), glob(self::sourceDir().'/*.svg') ?: []);
        sort($names);

        return $names;
    }

    public static function build(): string
    {
        $symbols = array_map(
            fn (string $name): string => self::symbol($name, (string) file_get_contents(self::sourceDir()."/{$name}.svg")),
            self::names(),
        );

        return '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">'."\n"
            .implode("\n", $symbols)."\n"
            .'</svg>'."\n";
    }

    public static function symbol(string $name, string $source): string
    {
        if (! preg_match('/^[a-z0-9][a-z0-9-]*$/', $name)) {
            throw new RuntimeException("Недопустимое имя иконки: {$name}");
        }

        $doc = new DOMDocument;
        $doc->preserveWhiteSpace = false;
        if (! @$doc->loadXML($source, LIBXML_NONET)) {
            throw new RuntimeException("Невалидный SVG: {$name}.svg");
        }

        $svg = $doc->documentElement;
        if (! $svg instanceof DOMElement || $svg->localName !== 'svg') {
            throw new RuntimeException("Корневой элемент не <svg>: {$name}.svg");
        }

        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('svg', self::SVG_NS);
        foreach (iterator_to_array($xpath->query('//comment() | //svg:title | //svg:desc | //svg:metadata')) as $node) {
            $node->parentNode->removeChild($node);
        }

        self::scopeIds($xpath, $name);

        $attributes = ['id="'.$name.'"', 'viewBox="'.self::viewBox($svg, $name).'"'];
        foreach ($svg->attributes as $attribute) {
            if ($attribute->name === 'viewBox' || in_array($attribute->name, self::DROPPED_ROOT_ATTRIBUTES, true)) {
                continue;
            }
            $attributes[] = $attribute->name.'="'.htmlspecialchars($attribute->value, ENT_XML1 | ENT_QUOTES).'"';
        }

        $inner = '';
        foreach ($svg->childNodes as $child) {
            $inner .= $doc->saveXML($child);
        }
        $inner = preg_replace('/\s+xmlns(:xlink)?="[^"]*"/', '', $inner);

        return '<symbol '.implode(' ', $attributes).'>'.trim($inner).'</symbol>';
    }

    private static function viewBox(DOMElement $svg, string $name): string
    {
        $viewBox = trim(preg_replace('/[\s,]+/', ' ', $svg->getAttribute('viewBox')));
        if ($viewBox !== '') {
            return $viewBox;
        }

        $width = (float) $svg->getAttribute('width');
        $height = (float) $svg->getAttribute('height');
        if ($width <= 0 || $height <= 0) {
            throw new RuntimeException("Нет viewBox и размеров: {$name}.svg");
        }

        return "0 0 {$width} {$height}";
    }

    /**
     * Внутри спрайта все id делят один документ: неиспользуемые убираем,
     * а те, на которые ссылаются (clipPath, mask, градиенты), префиксуем именем иконки.
     */
    private static function scopeIds(DOMXPath $xpath, string $name): void
    {
        $references = [];
        foreach ($xpath->query('//@*') as $attribute) {
            if (preg_match_all('/url\(\s*[\'"]?#([^\'")\s]+)/', $attribute->value, $m)) {
                array_push($references, ...$m[1]);
            }
            if ($attribute->localName === 'href' && str_starts_with($attribute->value, '#')) {
                $references[] = substr($attribute->value, 1);
            }
        }
        $references = array_flip($references);

        foreach (iterator_to_array($xpath->query('//*[@id]')) as $element) {
            $id = $element->getAttribute('id');
            if ($element === $xpath->document->documentElement) {
                continue;
            }
            if (isset($references[$id])) {
                $element->setAttribute('id', "{$name}--{$id}");
            } else {
                $element->removeAttribute('id');
            }
        }

        foreach ($xpath->query('//@*') as $attribute) {
            $value = preg_replace_callback(
                '/url\(\s*([\'"]?)#([^\'")\s]+)\1\s*\)/',
                fn (array $m): string => isset($references[$m[2]]) ? "url(#{$name}--{$m[2]})" : $m[0],
                $attribute->value,
            );
            if ($attribute->localName === 'href' && str_starts_with($value, '#') && isset($references[substr($value, 1)])) {
                $value = "#{$name}--".substr($value, 1);
            }
            if ($value !== $attribute->value) {
                $attribute->value = $value;
            }
        }
    }
}
