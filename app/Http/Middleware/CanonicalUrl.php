<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Канонический вид адресов страниц: нижний регистр и слэш в конце.
 * /Slots/Foo, /slots/foo, /DE/Authors → один 301 на /slots/foo/, /de/authors/. Query-строка не меняется.
 */
class CanonicalUrl
{
    /**
     * Служебные адреса без канонизации: админка, ассеты, подписанные ссылки и токены
     * (в них важен регистр), JSON/HTML-эндпоинты для JS.
     */
    private const EXCLUDED = '#^(?:(?:de|fr)/)?(?:admin|livewire[^/]*|filament|storage|auth|newsletter|profile/email|up|search|translate|logout)(?:/|$)#i';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        $path = $request->getPathInfo();
        $target = self::canonicalPath($path);

        if ($target === $path) {
            return $next($request);
        }

        $query = (string) $request->server('QUERY_STRING', '');

        return new RedirectResponse(
            $request->getSchemeAndHttpHost().$request->getBaseUrl().$target.($query !== '' ? '?'.$query : ''),
            301,
        );
    }

    public static function canonicalPath(string $path): string
    {
        $trimmed = ltrim($path, '/');

        if ($trimmed === '' || ! self::isPage($trimmed)) {
            return $path;
        }

        return '/'.rtrim(strtolower($trimmed), '/').'/';
    }

    /**
     * Нужен ли пути страницы слэш в конце (для генерации ссылок в localized_url()).
     */
    public static function isPage(string $path): bool
    {
        $path = trim($path, '/');

        if ($path === '' || str_contains($path, '?') || str_contains($path, '#') || str_contains($path, '//')) {
            return false;
        }

        if (preg_match(self::EXCLUDED, $path)) {
            return false;
        }

        return ! str_contains((string) strrchr('/'.$path, '/'), '.');
    }
}
