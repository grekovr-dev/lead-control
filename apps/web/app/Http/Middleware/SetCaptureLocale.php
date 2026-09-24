<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetCaptureLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->header('X-Landing-Locale');

        $resolvedLocale = is_string($locale) && in_array($locale, config('app.available_locales'), true)
            ? $locale
            : config('app.locale');

        app()->setLocale($resolvedLocale);

        return $next($request);
    }
}
