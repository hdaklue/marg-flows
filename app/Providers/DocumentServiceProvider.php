<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Document\DocumentManagerInterface;
use App\Contracts\Document\DocumentTemplateTranslatorInterface;
use App\Services\Document\ConfigBuilder\EditorConfigManager;
use App\Services\Document\ConfigBuilder\EditorManager;
use App\Services\Document\ContentBlocks\BudgetBlock;
use App\Services\Document\ContentBlocks\ListBlock;
use App\Services\Document\ContentBlocks\ObjectiveBlock;
use App\Services\Document\ContentBlocks\PersonaBlock;
use App\Services\Document\ContentBlocks\ResizableImageBlock;
use App\Services\Document\ContentBlocks\VideoUploadBlock;
use App\Services\Document\DocumentService;
use App\Services\Document\Facades\EditorBuilder;
use App\Services\Document\Facades\EditorConfigBuilder;
use App\Services\Document\Templates\DocumentTemplateManager;
use App\Services\Document\Templates\Translation\DocumentTemplateTranslator;
use BumpCore\EditorPhp\EditorPhp;
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
        $this->app->singleton(
            EditorConfigBuilder::class,
            fn ($app) => new EditorConfigManager($app),
        );
        $this->app->bind(DocumentManagerInterface::class, DocumentService::class);
        $this->app->singleton('document.template', function ($app) {
            return new DocumentTemplateManager;
        });

        $this->app->singleton(EditorBuilder::class, fn ($app) => new EditorManager($app));

        $this->app->singleton(
            DocumentTemplateTranslatorInterface::class,
            DocumentTemplateTranslator::class,
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
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
}
