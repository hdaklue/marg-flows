<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Document\ConfigBuilder\ConfigManager;
use App\Services\Document\ConfigBuilder\DocumentManager;
use App\Services\Document\Facades\ConfigBuilder;
use App\Services\Document\Facades\DocumentBuilder;
use App\Services\Document\Facades\DocumentResolver;
use App\Services\Document\Resolver\DocumentStrategyResolver;
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

        $this->app->singleton(ConfigBuilder::class, fn ($app) => new ConfigManager($app));

        $this->app->singleton(DocumentBuilder::class, fn ($app) => new DocumentManager($app));
        $this->app->singleton(DocumentResolver::class, fn ($app) => new DocumentStrategyResolver);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
