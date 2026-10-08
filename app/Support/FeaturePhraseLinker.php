<?php

namespace App\Support;

class FeaturePhraseLinker
{
    /**
     * Exact phrases from the Game information filters, longest first.
     *
     * @return array<string, string>
     */
    public static function phrases(): array
    {
        return [
            'tumbling wins' => 'tumbling',
            'gamble feature' => 'gamble',
            'scatter symbol' => 'scatter',
            'progressive jackpot' => 'progressive',
            'bonus buy' => 'bonus-buy',
            'free spins' => 'free-spins',
        ];
    }

    public static function link(?string $html, ?string $locale = null): string
    {
        if (! filled($html)) {
            return '';
        }

        $insideSkip = 0;

        return (string) preg_replace_callback('/(<[^>]+>)|([^<]+)/u', function (array $match) use ($locale, &$insideSkip): string {
            if (($match[1] ?? '') !== '') {
                $tag = strtolower($match[1]);
                if (preg_match('/^<(a|script|style|code|pre)\b/', $tag) === 1) {
                    $insideSkip++;
                }
                if (preg_match('/^<\/(a|script|style|code|pre)>/', $tag) === 1) {
                    $insideSkip = max(0, $insideSkip - 1);
                }

                return $match[1];
            }

            if ($insideSkip > 0) {
                return $match[2];
            }

            return self::linkPlainText($match[2], $locale);
        }, $html) ?? $html;
    }

    private static function linkPlainText(string $text, ?string $locale): string
    {
        foreach (self::phrases() as $phrase => $slug) {
            $pattern = '/(?<![\p{L}\p{N}])('.preg_quote($phrase, '/').')(?![\p{L}\p{N}])/iu';
            $url = rtrim(localized_url($locale, 'by-feature/'.$slug), '/').'/';
            $text = preg_replace(
                $pattern,
                '<a class="section__link" href="'.e($url).'">$1</a>',
                $text
            ) ?? $text;
        }

        return $text;
    }
}
