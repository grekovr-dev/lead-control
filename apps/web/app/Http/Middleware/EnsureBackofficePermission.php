<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\BackofficePermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureBackofficePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        abort_unless(BackofficePermissions::can($permission), 403);

        return $next($request);
    }
}
