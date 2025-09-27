<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Document\DocumentManagerInterface;
use App\Contracts\Document\DocumentTemplateTranslatorInterface;
use App\Services\Directory\Managers\DocumentDirectoryManager;
use App\Services\Document\AssetServing\Resolvers\DocumentFileResolver;
use App\Services\Document\ConfigBuilder\EditorConfigManager;
use App\Services\Document\ConfigBuilder\EditorManager;
use App\Services\Document\ContentBlocks\BudgetBlock;
use App\Services\Document\ContentBlocks\ListBlock;
use App\Services\Document\ContentBlocks\ObjectiveBlock;
use App\Services\Document\ContentBlocks\PersonaBlock;
use App\Services\Document\ContentBlocks\ResizableImageBlock;
use App\Services\Document\ContentBlocks\VideoUploadBlock;
use App\Services\Document\Contracts\DocumentVersionContract;
use App\Services\Document\DocumentService;
use App\Services\Document\DocumentVersionService;
use App\Services\Document\Facades\EditorBuilder;
use App\Services\Document\Facades\EditorConfigBuilder;
use App\Services\Document\Sessions\VideoUploadSessionManager;
use App\Services\Document\Templates\DocumentTemplateManager;
use App\Services\Document\Templates\Translation\DocumentTemplateTranslator;
use BumpCore\EditorPhp\EditorPhp;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Document Service Provider.
 *
 * Registers all Document service components including EditorJS blocks,
 * asset serving/uploading, templates, and HTTP routes. Provides a
 * complete micro-application for document management functionality.
 */
final class DocumentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Core Document Services
        $this->app->bind(DocumentManagerInterface::class, DocumentService::class);
        $this->app->bind(DocumentVersionContract::class, DocumentVersionService::class);

        // Editor Services
        $this->app->singleton(
            EditorConfigBuilder::class,
            fn ($app) => new EditorConfigManager($app),
        );
        $this->app->singleton(EditorBuilder::class, fn ($app) => new EditorManager($app));

        // Template Services
        $this->app->singleton('document.template', function ($app) {
            return new DocumentTemplateManager;
        });
        $this->app->singleton(
            DocumentTemplateTranslatorInterface::class,
            DocumentTemplateTranslator::class,
        );

        // Asset Serving Services
        $this->app->singleton(DocumentFileResolver::class, function ($app) {
            return new DocumentFileResolver($app->make(DocumentDirectoryManager::class));
        });

        // Asset Uploading Services
        $this->app->singleton(VideoUploadSessionManager::class);

        // Service Aliases
        $this->app->alias(DocumentFileResolver::class, 'document.file-resolver');
        $this->app->alias(VideoUploadSessionManager::class, 'document.video-session-manager');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register EditorJS Content Blocks
        $this->registerEditorBlocks();

        // Load Document HTTP Routes
        $this->loadDocumentRoutes();

        // Configure Document Services
        $this->configureDocumentServices();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            DocumentManagerInterface::class,
            DocumentVersionContract::class,
            EditorConfigBuilder::class,
            EditorBuilder::class,
            DocumentTemplateTranslatorInterface::class,
            DocumentFileResolver::class,
            VideoUploadSessionManager::class,
            'document.template',
            'document.file-resolver',
            'document.video-session-manager',
        ];
    }

    /**
     * Register EditorJS content blocks.
     */
    private function registerEditorBlocks(): void
    {
        EditorPhp::register([
            'nestedList' => ListBlock::class,
            'list' => ListBlock::class,
            'objective' => ObjectiveBlock::class,
            'images' => ResizableImageBlock::class,
            'videoUpload' => VideoUploadBlock::class,
            'budget' => BudgetBlock::class,
            'persona' => PersonaBlock::class,
        ]);
    }

    /**
     * Load Document service HTTP routes.
     */
    private function loadDocumentRoutes(): void
    {
        Route::middleware('web')->group(base_path('app/Services/Document/HTTP/routes.php'));
    }

    /**
     * Configure Document service defaults.
     */
    private function configureDocumentServices(): void
    {
        // Configure asset upload limits
        config([
            'document.assets.images.max_size' => 10 * 1024 * 1024, // 10MB
            'document.assets.videos.max_size' => 500 * 1024 * 1024, // 500MB
            'document.assets.videos.allowed_formats' => ['mp4', 'webm', 'mov'],
            'document.assets.images.allowed_formats' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        ]);

        // Configure video upload sessions
        config([
            'document.video_sessions.ttl' => 3600, // 1 hour
            'document.video_sessions.cleanup_interval' => 300, // 5 minutes
        ]);
    }
}
