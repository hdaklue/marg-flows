<?php

declare(strict_types=1);

namespace App\Services\Document\Facades;

use App\Services\Document\ConfigBuilder\EditorManager;
use Illuminate\Support\Facades\Facade;

final class EditorBuilder extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return EditorManager::class;
    }
}
