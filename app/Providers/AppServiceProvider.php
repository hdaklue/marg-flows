<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Document\DocumentManagerInterface;
use App\Listeners\TenantEventSubscriber;
use App\Models\Document;
use App\Models\Flow;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Document\DocumentService;
use App\Services\Flow\TimeProgressService;
use App\Services\Role\RoleAssignmentService;
use Filament\Actions\Action;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
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
        $this->app->singleton('role.manager', fn (): RoleAssignmentService => new RoleAssignmentService);
        $this->app->singleton('document.manager', fn (): DocumentService => new DocumentService);
        $this->app->bind(DocumentManagerInterface::class, DocumentService::class);

        $this->configureGate();
        $this->configureModel();
        // $this->configureVite();

        if ($this->app->environment('local') && class_exists(TelescopeServiceProvider::class)) {
            $this->app->register(TelescopeServiceProvider::class);

        }

        Action::configureUsing(function (Action $action): void {

            $action->size(ActionSize::ExtraSmall);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        Event::subscribe(TenantEventSubscriber::class);
        FilamentAsset::register([
            AlpineComponent::make('editorJs', __DIR__ . '/../../resources/js/dist/components/editorjs.js'),
            AlpineComponent::make('chunkedFileUploadComponent', __DIR__ . '/../../resources/js/dist/components/chunked-file-upload.js'),
            AlpineComponent::make('documentEditor', __DIR__ . '/../../resources/js/dist/components/document.js'),
            AlpineComponent::make('mentionableText', __DIR__ . '/../../resources/js/dist/components/mentionable.js'),
            AlpineComponent::make('voiceRecorder', __DIR__ . '/../../resources/js/dist/components/voice-recorder.js'),
            AlpineComponent::make('videoRecorder', __DIR__ . '/../../resources/js/dist/components/video-recorder.js'),
            AlpineComponent::make('audioPlayer', __DIR__ . '/../../resources/js/dist/components/audio-player.js'),
            Css::make('chunkedFileUploadCss', __DIR__ . '/../../resources/css/components/chunked-file-upload.css')
                ->loadedOnRequest(),
            Css::make('mentionableTextCss', __DIR__ . '/../../resources/css/components/mentionable-text.css')
                ->loadedOnRequest(),
            Css::make('voiceRecorderCss', __DIR__ . '/../../resources/css/components/voice-recorder.css')
                ->loadedOnRequest(),
            Css::make('videoRecorderCss', __DIR__ . '/../../resources/css/components/video-recorder.css')
                ->loadedOnRequest(),

            // Js::make('editorJs', __DIR__ . '/../../public/build/assets/index-C9DEfZiz.js')->loadedOnRequest(),
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
        Gate::defaultDenialResponse(
            Response::denyAsNotFound(),
        );
    }
}
