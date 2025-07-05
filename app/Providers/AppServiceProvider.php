<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\TenantEventSubscriber;
use App\Models\Flow;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Flow\TimeProgressService;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TimeProgressService::class);

        $this->configureGate();
        $this->configureModel();
        // $this->configureVite();

        if ($this->app->environment('local') && class_exists(TelescopeServiceProvider::class)) {
            $this->app->register(TelescopeServiceProvider::class);

        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        Event::subscribe(TenantEventSubscriber::class);
        FilamentAsset::register([
            AlpineComponent::make('editorJs', __DIR__ . '/../../resources/js/dist/components/editorjs.js'),
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
