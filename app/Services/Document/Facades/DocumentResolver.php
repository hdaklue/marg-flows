<?php

declare(strict_types=1);

namespace App\Services\Document\Facades;

use App\Services\Document\Resolver\DocumentStrategyResolver;
use Illuminate\Support\Facades\Facade;

final class DocumentResolver extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return DocumentStrategyResolver::class;
    }
}
