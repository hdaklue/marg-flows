<?php

declare(strict_types=1);

namespace App\Facades;

use App\Contracts\Deliverables\DeliverableServiceContract;
use App\Services\Deliverable\DeliverablesManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static DeliverableServiceContract design()
 * @method static DeliverableServiceContract driver(string $driver = null)
 *
 * @see DeliverablesManager
 */
final class DeliverableBuilder extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DeliverablesManager::class;
    }
}
