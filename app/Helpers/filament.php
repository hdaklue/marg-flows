<?php

declare(strict_types=1);

use App\Models\Tenant;
use Illuminate\Contracts\Auth\Authenticatable;

if (! function_exists('filamentUser')) {
    /**
     * Get the currently authenticated user from the Filament panel.
     *
     * @return \App\Models\User|null
     */
    function filamentUser(): ?Authenticatable
    {
        return filament()->auth()->user();
    }

}

if (! function_exists('filamentTenant')) {
    /**
     * Get the currently resolved Filament tenant, if any.
     */
    function filamentTenant(): ?Tenant
    {
        return filament()->getTenant();
    }
}
