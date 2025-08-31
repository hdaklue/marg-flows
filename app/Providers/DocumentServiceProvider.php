<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Document\ConfigBuilder\EditorConfigManager;
use App\Services\Document\ConfigBuilder\EditorManager;
use App\Services\Document\Facades\DocumentResolver;
use App\Services\Document\Facades\EditorBuilder;
use App\Services\Document\Facades\EditorConfigBuilder;
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

        $this->app->singleton(EditorConfigBuilder::class, fn ($app) => new EditorConfigManager($app));

        $this->app->singleton(EditorBuilder::class, fn ($app) => new EditorManager($app));
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
