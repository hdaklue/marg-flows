<?php

declare(strict_types=1);

namespace App\Http\Middleware\Filament;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class CanAccessAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request):Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()->canAccessAdmin(), 404);

        return $next($request);
    }
}
