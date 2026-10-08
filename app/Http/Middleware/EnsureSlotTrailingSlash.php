<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSlotTrailingSlash
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->getPathInfo();

        if (preg_match('#^/(?:de|fr)/slots/[^/]+$#', $path) || preg_match('#^/slots/[^/]+$#', $path)) {
            if (! str_ends_with($path, '/')) {
                $qs = $request->getQueryString();

                return redirect($path.'/'.($qs ? '?'.$qs : ''), 301);
            }
        }

        return $next($request);
    }
}
