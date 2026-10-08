<?php

namespace App\Support;

use App\Filament\RichContent\PostFigureBlock;
use DOMDocument;
use DOMElement;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Support\Str;
use Throwable;

class PostBody
{
    public static function render(mixed $content): string
    {
        if (blank($content)) {
            return '';
        }

        self::mirrorEmbedded($content);

        if (is_string($content) && ! str_contains($content, '<')) {
            return '<p>'.e($content).'</p>';
        }

        try {
            $html = RichContentRenderer::make(is_array($content) ? $content : (string) $content)
                ->customBlocks([PostFigureBlock::class])
                ->fileAttachmentsDisk('r2')
                ->fileAttachmentsVisibility('private')
                ->toHtml();
        } catch (Throwable) {
            $html = is_string($content) ? Str::sanitizeHtml($content) : '';
        }

        return self::rewriteImages($html);
    }

    public static function mirrorEmbedded(mixed $content): void
    {
        $raw = is_string($content) ? $content : json_encode($content);
        if (! is_string($raw) || $raw === '') {
            return;
        }

        if (! preg_match_all('#(?:posts/body|comments)/[A-Za-z0-9._/-]+#', $raw, $matches)) {
            return;
        }

        foreach (array_unique($matches[0]) as $path) {
            try {
                MediaMirror::mirrorPath($path);
            } catch (Throwable) {
                // R2 may be unconfigured in tests or local without credentials.
            }
        }
    }

    public static function rewriteImages(string $html): string
    {
        if ($html === '' || ! str_contains($html, '<img')) {
            return $html;
        }

        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?><div id="post-body-root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $dom->getElementById('post-body-root');
        if (! $root) {
            return $html;
        }

        $images = [];
        foreach ($root->getElementsByTagName('img') as $img) {
            if ($img instanceof DOMElement) {
                $images[] = $img;
            }
        }

        foreach ($images as $img) {
            $id = $img->getAttribute('data-id');
            if ($id !== '' && ! str_starts_with($id, 'http') && str_contains($id, '/')) {
                try {
                    MediaMirror::mirrorPath($id);
                } catch (Throwable) {
                    // Keep the stored id and fall through to media_url().
                }
                $img->setAttribute('src', media_url($id));
            }

            $img->removeAttribute('width');
            $img->removeAttribute('height');
            $img->removeAttribute('style');
            if (! $img->hasAttribute('loading')) {
                $img->setAttribute('loading', 'lazy');
            }

            $alt = trim($img->getAttribute('alt'));
            $parent = $img->parentNode;
            if ($alt === '' || ! $parent || strtolower($parent->nodeName) === 'figure') {
                continue;
            }

            $figure = $dom->createElement('figure');
            $caption = $dom->createElement('figcaption');
            $caption->appendChild($dom->createTextNode($alt));

            if (strtolower($parent->nodeName) === 'p' && $parent->parentNode && trim($parent->textContent ?? '') === $alt) {
                $parent->parentNode->replaceChild($figure, $parent);
            } else {
                $parent->replaceChild($figure, $img);
            }

            $figure->appendChild($img);
            $figure->appendChild($caption);
        }

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return $out;
    }
}
