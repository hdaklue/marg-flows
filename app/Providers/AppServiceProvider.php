<?php

namespace App\Providers;

use App\Models\Flow;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Flow\FlowProgressService;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FlowProgressService::class);
        Model::shouldBeStrict();
        Gate::defaultDenialResponse(
            Response::denyAsNotFound()
        );

        Relation::enforceMorphMap([
            'user' => User::class,
            'tenant' => Tenant::class,
            'flow' => Flow::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
