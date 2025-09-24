<?php

declare(strict_types=1);

namespace App\Services\Video;

use App\Services\Video\Services\VideoNamingService;
use Illuminate\Support\ServiceProvider;

final class VideoServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind('video', fn($app) => new VideoManager($app));

        $this->app->bind(VideoNamingService::class, fn() => new VideoNamingService());
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration file
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/video.php' => config_path('video.php'),
            ], 'video-config');
        }
    }
}
