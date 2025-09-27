<?php

declare(strict_types=1);

namespace App\Providers;

use App\Facades\DeliverableBuilder;
use App\Listeners\TenantEventSubscriber;
use App\Models\Document;
use App\Models\Flow;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Deliverable\DeliverablesManager;
use App\Services\Directory\Managers\ChunksDirectoryManager;
use App\Services\Directory\Managers\DocumentDirectoryManager;
use App\Services\Directory\Managers\SystemDirectoryManager;
use App\Services\Document\Contracts\DocumentVersionContract;
use App\Services\Document\DocumentService;
use App\Services\Document\DocumentVersionService;
use App\Services\Flow\TimeProgressService;
use App\Services\MentionService;
use App\Services\Role\RoleAssignmentService;
use App\Services\Upload\UploadSessionManager;
use Filament\Actions\Action;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Enums\Size;
use Filament\Support\Facades\FilamentAsset;
use Hdaklue\Actioncrumb\Config\ActioncrumbConfig;
use Hdaklue\Actioncrumb\Enums\SeparatorType;
use Hdaklue\Actioncrumb\Enums\TailwindColor;
use Hdaklue\Actioncrumb\Enums\ThemeStyle;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TimeProgressService::class);
        // $this->app->singleton('role.manager', fn (): RoleAssignmentService => new RoleAssignmentService);
        $this->app->singleton(DocumentVersionService::class);
        $this->app->bind(DocumentVersionContract::class, DocumentVersionService::class);
        $this->app->singleton('document.manager', fn (Application $app): DocumentService => new DocumentService($app->make(DocumentVersionContract::class)));
        $this->app->singleton('mention.service', fn (): MentionService => new MentionService);
        $this->app->singleton(
            DeliverableBuilder::class,
            fn (): DeliverablesManager => new DeliverablesManager($this->app),
        );
        $this->app->singleton(
            UploadSessionManager::class,
            fn ($app) => new UploadSessionManager($app),
        );

        // Register independent directory managers
        $this->app->singleton(DocumentDirectoryManager::class);
        $this->app->singleton(SystemDirectoryManager::class);
        $this->app->singleton(ChunksDirectoryManager::class);

        $this->configureGate();

        // $this->configureVite();

        if ($this->app->environment('local') && class_exists(TelescopeServiceProvider::class)) {
            $this->app->register(TelescopeServiceProvider::class);
        }

        Action::configureUsing(function (Action $action): void {
            $action->size(Size::ExtraSmall);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureModel();

        ActioncrumbConfig::make()
            ->themeStyle(ThemeStyle::Simple) // Simple, Rounded, Square
            ->separatorType(SeparatorType::Line) // Chevron, Line
            ->primaryColor(TailwindColor::Emerald) // Any Tailwind color
            ->secondaryColor(TailwindColor::Zinc) // Secondary accents
            ->compactMenuOnMobile()
            ->bind();

        Event::subscribe(TenantEventSubscriber::class);
        FilamentAsset::register([
            AlpineComponent::make(
                'editorJs',
                __DIR__ . '/../../resources/js/dist/components/editorjs.js',
            ),
            AlpineComponent::make(
                'chunkedFileUploadComponent',
                __DIR__ . '/../../resources/js/dist/components/chunked-file-upload.js',
            ),
            AlpineComponent::make(
                'documentEditor',
                __DIR__ . '/../../resources/js/dist/components/document.js',
            ),
            AlpineComponent::make(
                'mentionableText',
                __DIR__ . '/../../resources/js/dist/components/mentionable.js',
            ),
            AlpineComponent::make(
                'voiceRecorder',
                __DIR__ . '/../../resources/js/dist/components/voice-recorder.js',
            ),
            AlpineComponent::make(
                'videoRecorder',
                __DIR__ . '/../../resources/js/dist/components/video-recorder.js',
            ),
            AlpineComponent::make(
                'audioPlayer',
                __DIR__ . '/../../resources/js/dist/components/audio-player.js',
            ),
            AlpineComponent::make(
                'designAnnotation',
                __DIR__ . '/../../resources/js/dist/components/design-annotation.js',
            ),
            Css::make(
                'chunkedFileUploadCss',
                __DIR__ . '/../../resources/css/components/chunked-file-upload.css',
            )->loadedOnRequest(),
            Css::make(
                'mentionableTextCss',
                __DIR__ . '/../../resources/css/components/mentionable-text.css',
            )->loadedOnRequest(),
            Css::make(
                'voiceRecorderCss',
                __DIR__ . '/../../resources/css/components/voice-recorder.css',
            )->loadedOnRequest(),
            Css::make(
                'videoRecorderCss',
                __DIR__ . '/../../resources/css/components/video-recorder.css',
            )->loadedOnRequest(),
            Js::make(
                'alpinesortable',
                __DIR__ . '/../../resources/js/dist/components/alpine-sortable.js',
            ),
            // Js::make('editorJs', __DIR__ . '/../../resources/js/components/editorjs/index.js'),
        ]);
    }

    protected function configureVite()
    {
        Vite::useAggressivePrefetching();
    }

    protected function configureModel()
    {
        Relation::enforceMorphMap([
            'user' => User::class,
            'tenant' => Tenant::class,
            'flow' => Flow::class,
            'document' => Document::class,
        ]);
        Model::shouldBeStrict();
    }

    protected function configureGate()
    {
        if (app()->isProduction()) {
            Gate::defaultDenialResponse(Response::denyAsNotFound());
        }
    }
}
