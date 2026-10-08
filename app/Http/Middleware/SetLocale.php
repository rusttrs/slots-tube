<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isAdminRequest($request)) {
            app()->setLocale('ru');

            return $next($request);
        }

        $supported = config('app.available_locales', ['en', 'de', 'fr']);
        $default = config('app.fallback_locale', 'en');
        $segment = $request->segment(1);

        if (in_array($segment, $supported, true) && $segment !== $default) {
            app()->setLocale($segment);
        } else {
            app()->setLocale($default);
        }

        Carbon::setLocale(app()->getLocale());

        return $next($request);
    }

    private function isAdminRequest(Request $request): bool
    {
        if ($request->is('admin', 'admin/*')) {
            return true;
        }

        if (! $request->is('livewire/*')) {
            return false;
        }

        $path = parse_url((string) $request->headers->get('referer', ''), PHP_URL_PATH) ?: '';
        if ($path === '/admin' || str_starts_with($path, '/admin/')) {
            return true;
        }

        $components = $request->input('components');
        if (! is_array($components)) {
            return false;
        }

        foreach ($components as $component) {
            $snapshot = $component['snapshot'] ?? '';
            if (is_string($snapshot) && str_contains($snapshot, 'Filament\\')) {
                return true;
            }
        }

        return false;
    }
}
