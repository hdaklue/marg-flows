<?php

declare(strict_types=1);
use App\Providers\AppServiceProvider;
use App\Providers\Filament\PortalPanelProvider;
use App\Services\Video\VideoServiceProvider;
use Hdaklue\MargRbac\Providers\MargRbacServiceProvider;

return [
    AppServiceProvider::class,
    PortalPanelProvider::class,
    VideoServiceProvider::class,
    MargRbacServiceProvider::class,
];
