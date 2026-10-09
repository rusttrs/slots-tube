<?php

use App\Http\Middleware\CanonicalUrl;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->append(CanonicalUrl::class);
        $middleware->redirectGuestsTo(fn () => localized_url(null, '') . '?auth=1');
        $middleware->web(append: [
            SetLocale::class,
            EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Unmatched routes skip the web middleware, so SetLocale never runs for them.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            $segment = $request->segment(1);
            if (in_array($segment, config('app.available_locales', []), true)) {
                app()->setLocale($segment);
            }
        });
    })->create();
