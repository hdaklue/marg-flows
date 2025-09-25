<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

if (! function_exists('filamentUser')) {
    /**
     * Get the currently authenticated user from the Filament panel.
     *
     * @return User
     *
     * @throws \Throwable
     */
    function filamentUser(): Authenticatable
    {
        throw_unless(
            filament()->auth()->user(),
            new \Exception('Cannot resolve Filament Auth User'),
        );

        return filament()->auth()->user();
    }
}

/**
 * Get the currently enabled Filament Tenant.
 *
 * @return Tenant
 *
 * @throws \Exception
 */
if (! function_exists('filamentTenant')) {
    /**
     * Get the currently resolved Filament tenant, if any.
     *
     * @throws \Throwable
     */
    function filamentTenant(): Tenant
    {
        throw_unless(filament()->getTenant(), new \Exception('Cannot resolve Filament Tenant'));

        /** @var Tenant */
        return filament()->getTenant();
    }
}

if (! function_exists('viteBuiltPath')) {
    /**
     * Get the physical disk path of a built Vite asset from the manifest.
     *
     * @param  string  $entry  Relative source path like 'resources/js/components/editorjs/index.js'
     *
     * @throws \Exception
     * @throws \Throwable
     */
    function viteBuiltPath(string $entry): string
    {
        $manifestFile = public_path('build/manifest.json');

        throw_unless(
            file_exists($manifestFile),
            new \Exception('Vite manifest file not found: ' . $manifestFile),
        );

        $manifest = json_decode(file_get_contents($manifestFile), true);

        throw_unless(
            isset($manifest[$entry]['file']),
            new \Exception("Vite entry not found in manifest: {$entry}"),
        );

        return public_path('build/' . $manifest[$entry]['file']);
    }
}
