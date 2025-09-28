<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\DocumentServiceProvider;
use App\Providers\Filament\PortalPanelProvider;
use App\Providers\FileServingServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\NapTabServiceProvider;
use App\Services\Video\VideoServiceProvider;
use Hdaklue\Actioncrumb\Providers\ActioncrumbServiceProvider;
use Hdaklue\MargRbac\Providers\MargRbacServiceProvider;

return [
    AppServiceProvider::class,
    DocumentServiceProvider::class,
    PortalPanelProvider::class,
    FileServingServiceProvider::class,
    HorizonServiceProvider::class,
    NapTabServiceProvider::class,
    VideoServiceProvider::class,
    MargRbacServiceProvider::class,
    ActioncrumbServiceProvider::class,
];
