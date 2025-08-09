<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Document\BlockManager;
use App\Services\Document\ConfigManager;
use App\Services\Document\Facades\BlockBuilder;
use App\Services\Document\Facades\ConfigBuilder;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider for BlockManager.
 */
final class DocumentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(BlockBuilder::class, function ($app) {
            return new BlockManager($app);
        });
        $this->app->singleton(ConfigBuilder::class, function ($app) {
            return new ConfigManager($app);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
