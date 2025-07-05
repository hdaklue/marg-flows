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

if (! function_exists('viteBuiltPath')) {
    /**
     * Get the physical disk path of a built Vite asset from the manifest.
     *
     * @param  string  $entry  Relative source path like 'resources/js/components/editorjs/index.js'
     *
     * @throws \Exception
     */
    function viteBuiltPath(string $entry): string
    {
        $manifestFile = public_path('build/manifest.json');

        if (! file_exists($manifestFile)) {
            throw new \Exception('Vite manifest file not found: ' . $manifestFile);
        }

        $manifest = json_decode(file_get_contents($manifestFile), true);

        if (! isset($manifest[$entry]['file'])) {
            throw new \Exception("Vite entry not found in manifest: {$entry}");
        }

        return public_path('build/' . $manifest[$entry]['file']);
    }
}
