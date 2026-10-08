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
        if (! filled($path)) {
            return asset('assets/images/header/1a6dc.svg');
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $path = ltrim($path, '/');
        if (str_starts_with($path, 'assets/')) {
            return asset($path);
        }

        $bundled = public_path('assets/images/'.$path);
        if (is_file($bundled)) {
            return asset('assets/images/'.$path);
        }

        try {
            $public = \Illuminate\Support\Facades\Storage::disk('public');
            if ($public->exists($path)) {
                return $public->url($path);
            }
        } catch (\Throwable) {
            // continue to R2
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

if (! function_exists('parse_symbol_payouts')) {
    /**
     * @return list<array{mult: string, val: string}>|null
     */
    function parse_symbol_payouts(?string $text): ?array
    {
        $lines = lines_to_array($text);
        if ($lines === []) {
            return null;
        }

        $rows = [];
        foreach ($lines as $line) {
            if (preg_match('/^(x\s*\d+)\s+(.+)$/iu', $line, $match) !== 1) {
                return null;
            }

            $rows[] = [
                'mult' => strtolower(str_replace(' ', '', $match[1])),
                'val' => $match[2],
            ];
        }

        return $rows;
    }
}

if (! function_exists('safe_app_url')) {
    function safe_app_url(?string $candidate): ?string
    {
        if (! is_string($candidate) || $candidate === '') {
            return null;
        }

        $parts = parse_url($candidate);
        if (! is_array($parts) || empty($parts['host']) || empty($parts['scheme'])) {
            return null;
        }

        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return null;
        }

        if (strcasecmp($parts['host'], request()->getHost()) !== 0) {
            return null;
        }

        $path = $parts['path'] ?? '/';
        if (! str_starts_with($path, '/')) {
            return null;
        }

        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return request()->getSchemeAndHttpHost().$path.$query;
    }
}

if (! function_exists('remember_auth_return')) {
    function remember_auth_return(?string $candidate = null): void
    {
        $candidate = $candidate ?? (string) request()->input('return_to', '');
        $return = safe_app_url($candidate);
        if ($return) {
            request()->session()->put('auth_return', $return);
        }
    }
}

if (! function_exists('pull_auth_return')) {
    function pull_auth_return(): ?string
    {
        $stored = request()->session()->pull('auth_return');

        return is_string($stored) ? safe_app_url($stored) : null;
    }
}
