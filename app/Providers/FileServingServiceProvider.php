<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Directory\Managers\ChunksDirectoryManager;
use App\Services\Directory\Managers\DocumentDirectoryManager;
use App\Services\Directory\Managers\SystemDirectoryManager;
use App\Services\FileServing\Chunks\ChunkFileResolver;
use App\Services\FileServing\Document\DocumentFileResolver;
use App\Services\FileServing\System\SystemFileResolver;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * File Serving Service Provider.
 *
 * Registers all FileResolver services and loads their respective routes.
 * Provides centralized management for file serving functionality across
 * the application with proper service binding and route organization.
 */
final class FileServingServiceProvider extends ServiceProvider
{
    /**
     * Register file serving services.
     *
     * Binds all FileResolver implementations to the service container
     * for dependency injection throughout the application.
     */
    public function register(): void
    {
        // Register Document File Resolver
        // $this->app->singleton(DocumentFileResolver::class, function ($app) {
        //     return new DocumentFileResolver(
        //         $app->make(DocumentDirectoryManager::class),
        //     );
        // });

        // Register System File Resolver
        $this->app->singleton(SystemFileResolver::class, function ($app) {
            return new SystemFileResolver($app->make(SystemDirectoryManager::class));
        });

        // Register Chunk File Resolver
        $this->app->singleton(ChunkFileResolver::class, function ($app) {
            return new ChunkFileResolver($app->make(ChunksDirectoryManager::class));
        });

        // Register aliases for easier access
        
        $this->app->alias(SystemFileResolver::class, 'file-resolver.system');
        $this->app->alias(ChunkFileResolver::class, 'file-resolver.chunks');
    }

    /**
     * Bootstrap file serving services.
     *
     * Loads all file serving routes from their respective service directories
     * and performs any additional bootstrapping required for file operations.
     */
    public function boot(): void
    {
        $this->loadFileServingRoutes();
        $this->configureFileServingDefaults();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [

            SystemFileResolver::class,
            ChunkFileResolver::class,
            
            'file-resolver.system',
            'file-resolver.chunks',
        ];
    }

    /**
     * Load file serving routes from each service directory.
     *
     * Each FileResolver service manages its own routes for better organization
     * and separation of concerns. Routes are loaded with appropriate middleware
     * and prefixes for clean URL structure.
     */
    private function loadFileServingRoutes(): void
    {
        // Load Document FileResolver routes
        Route::middleware('web')->group(base_path('app/Services/FileServing/Document/routes.php'));

        // Load System FileResolver routes
        Route::middleware('web')->group(base_path('app/Services/FileServing/System/routes.php'));

        // Load Chunks FileResolver routes
        Route::middleware('web')->group(base_path('app/Services/FileServing/Chunks/routes.php'));
    }

    /**
     * Configure default settings for file serving operations.
     *
     * Sets up common configuration values and validation rules
     * that apply across all file serving services.
     */
    private function configureFileServingDefaults(): void
    {
        // Configure default file type validation
        $this->configureFileTypeValidation();

        // Set up default cache settings for file operations
        $this->configureFileCaching();

        // Register file serving middleware if needed
        $this->registerFileServingMiddleware();
    }

    /**
     * Configure file type validation rules.
     *
     * Defines allowed file types and size limits for different
     * file categories across all FileResolver services.
     */
    private function configureFileTypeValidation(): void
    {
        // Document files
        config([
            'fileserving.document.allowed_types.images' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'fileserving.document.allowed_types.videos' => ['mp4', 'webm', 'ogg'],
            'fileserving.document.allowed_types.documents' => ['pdf', 'doc', 'docx', 'txt'],
            'fileserving.document.max_size.images' => 10 * 1024 * 1024, // 10MB
            'fileserving.document.max_size.videos' => 100 * 1024 * 1024, // 100MB
            'fileserving.document.max_size.documents' => 50 * 1024 * 1024, // 50MB
        ]);

        // System files
        config([
            'fileserving.system.allowed_types.avatars' => ['jpg', 'jpeg', 'png', 'gif'],
            'fileserving.system.max_size.avatars' => 5 * 1024 * 1024, // 5MB
        ]);

        // Chunk files
        config([
            'fileserving.chunks.max_chunk_size' => 10 * 1024 * 1024, // 10MB
            'fileserving.chunks.max_total_size' => 1024 * 1024 * 1024, // 1GB
        ]);
    }

    /**
     * Configure file caching settings.
     *
     * Sets up caching strategies for file metadata and content
     * to improve performance across all file serving operations.
     */
    private function configureFileCaching(): void
    {
        config([
            'fileserving.cache.file_metadata_ttl' => 300, // 5 minutes
            'fileserving.cache.url_cache_ttl' => 3600, // 1 hour
            'fileserving.cache.enabled' => env('FILESERVING_CACHE_ENABLED', true),
        ]);
    }

    /**
     * Register file serving middleware.
     *
     * Registers any custom middleware needed for file serving operations
     * such as rate limiting, security headers, or access logging.
     */
    private function registerFileServingMiddleware(): void
    {
        // Register rate limiting for file uploads
        $this->app['router']->aliasMiddleware(
            'file-upload-throttle',
            ThrottleRequests::class . ':60,1',
        );

        // Register file access logging middleware if needed
        // $this->app['router']->aliasMiddleware('file-access-log', FileAccessLogMiddleware::class);
    }
}
