<?php

declare(strict_types=1);

namespace App\Services\Document\Facades;

use App\Services\Document\BlockManager;
use Illuminate\Support\Facades\Facade;

/**
 * BlockManager Facade.
 *
 * @method static ParagraphBlockBuilder paragraph()
 * @method static HeaderBlockBuilder header()
 *
 * @see BlockManagerService
 */
final class BlockBuilder extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return BlockManager::class;
    }
}
