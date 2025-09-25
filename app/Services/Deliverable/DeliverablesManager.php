<?php

declare(strict_types=1);

namespace App\Services\Deliverable;

use App\Contracts\Deliverables\DeliverableServiceContract;
use Illuminate\Support\Manager;
use InvalidArgumentException;

final class DeliverablesManager extends Manager
{
    public function getDefaultDriver()
    {
        throw new InvalidArgumentException('No default driver set for DeliverablesManager.');
    }

    public function design(): DeliverableServiceContract
    {
        return new DesignDeliverableService;
    }
}
