<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

if (! function_exists('localized_url')) {
    function localized_url(?string $locale = null, ?string $path = null): string
    {
        $locale = $locale ?: App::getLocale();
        $default = config('app.fallback_locale', 'en');
        $supported = config('app.available_locales', ['en', 'de', 'fr']);

        if (! in_array($locale, $supported, true)) {
            $locale = $default;
        }

        if ($path === null) {
            $path = request()->path();
            // strip existing locale prefix
            foreach ($supported as $loc) {
                if ($loc === $default) {
                    continue;
                }
                if ($path === $loc) {
                    $path = '';
                    break;
                }
                if (str_starts_with($path, $loc.'/')) {
                    $path = substr($path, strlen($loc) + 1);
                    break;
                }
            }
        }

        $path = ltrim((string) $path, '/');

        if ($locale === $default) {
            return $path === '' ? url('/') : url('/'.$path);
        }

        return $path === '' ? url('/'.$locale) : url('/'.$locale.'/'.$path);
    }
}

if (! function_exists('locale_route')) {
    function locale_route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        $locale = App::getLocale();
        $default = config('app.fallback_locale', 'en');

        if ($locale !== $default && ! array_key_exists('locale', (array) $parameters)) {
            // named routes without locale param — use localized_url with route path
        }

        return route($name, $parameters, $absolute);
    }
}


if (! function_exists('media_url')) {
    function media_url(?string $path): string
    {
        if (! $path) {
            return asset('assets/images/header/1a6dc.svg');
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        try {
            return \Illuminate\Support\Facades\Storage::disk('r2')->url($path);
        } catch (\Throwable) {
            return asset('storage/'.$path);
        }
    }
}

if (! function_exists('lines_to_array')) {
    /**
     * @return list<string>
     */
    function lines_to_array(?string $text): array
    {
        if (! filled($text)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $text) ?: [])));
    }
}
