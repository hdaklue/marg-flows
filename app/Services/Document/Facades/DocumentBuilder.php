<?php

declare(strict_types=1);

namespace App\Services\Document\Facades;

use App\Services\Document\ConfigBuilder\DocumentManager;
use Illuminate\Support\Facades\Facade;

final class DocumentBuilder extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return DocumentManager::class;
    }
}
