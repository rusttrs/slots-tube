<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleTranslate
{
    /**
     * @param  list<string>  $texts
     * @return list<string>
     */
    public function translateMany(array $texts, string $target, string $source = 'auto'): array
    {
        $target = Str::lower(Str::substr($target, 0, 2));
        if (! preg_match('/^[a-z]{2}$/', $target)) {
            return $texts;
        }

        $out = [];
        foreach ($texts as $text) {
            $out[] = $this->translateOne((string) $text, $target, $source);
        }

        return $out;
    }

    public function translateOne(string $text, string $target, string $source = 'auto'): string
    {
        $text = trim($text);
        if ($text === '') {
            return $text;
        }

        if (mb_strlen($text) > 5000) {
            $text = mb_substr($text, 0, 5000);
        }

        $cacheKey = 'gtranslate:'.sha1($source.'|'.$target.'|'.$text);
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        try {
            $response = Http::timeout(8)
                ->acceptJson()
                ->get('https://translate.googleapis.com/translate_a/single', [
                    'client' => 'gtx',
                    'sl' => $source,
                    'tl' => $target,
                    'dt' => 't',
                    'q' => $text,
                ]);

            if (! $response->successful()) {
                return $text;
            }

            $json = $response->json();
            if (! is_array($json) || ! isset($json[0]) || ! is_array($json[0])) {
                return $text;
            }

            $chunks = [];
            foreach ($json[0] as $part) {
                if (is_array($part) && isset($part[0]) && is_string($part[0])) {
                    $chunks[] = $part[0];
                }
            }

            $translated = trim(implode('', $chunks));
            if ($translated === '') {
                return $text;
            }

            Cache::put($cacheKey, $translated, now()->addDays(30));

            return $translated;
        } catch (\Throwable) {
            return $text;
        }
    }
}
