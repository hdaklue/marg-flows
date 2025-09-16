<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\DocumentServiceProvider;
use App\Providers\Filament\PortalPanelProvider;
use App\Providers\FileServingServiceProvider;
use App\Providers\NapTabServiceProvider;
use App\Services\Video\VideoServiceProvider;
use Hdaklue\MargRbac\Providers\MargRbacServiceProvider;

return [
    AppServiceProvider::class,
    DocumentServiceProvider::class,
    FileServingServiceProvider::class,
    PortalPanelProvider::class,
    VideoServiceProvider::class,
    MargRbacServiceProvider::class,
    NapTabServiceProvider::class,
];
