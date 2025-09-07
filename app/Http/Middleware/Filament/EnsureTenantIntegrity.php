<?php

declare(strict_types=1);

namespace App\Http\Middleware\Filament;

use App\Contracts\Tenant\BelongsToTenantContract;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureTenantIntegrity
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle($request, Closure $next)
    {
        // Check route parameters for tenant access
        foreach ($request->route()->parameters() as $parameter) {
            if ($parameter instanceof BelongsToTenantContract) {
                abort_if(
                    $parameter
                        ->getTenant()
                        ->getKey() !== filamentTenant()->getKey(),
                    404,
                );
            }
        }

        return $next($request);
    }
}
