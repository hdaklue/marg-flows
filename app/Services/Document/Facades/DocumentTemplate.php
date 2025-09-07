<?php

declare(strict_types=1);

namespace App\Services\Document\Facades;

use App\Services\Document\Templates\DocumentTemplateManager;
use Illuminate\Support\Facades\Facade;

final class DocumentTemplate extends Facade
{
    public static function getFacadeAccessor(): string
    {
        return DocumentTemplateManager::class;
    }
}
