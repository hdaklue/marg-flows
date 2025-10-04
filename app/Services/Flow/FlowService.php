<?php

declare(strict_types=1);

namespace App\Services\Flow;

use App\Models\Flow;
use App\Models\Tenant;
use App\Models\User;
use Hdaklue\Porter\Facades\Porter;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\LazyCollection;

final readonly class FlowService
{
    /**
     * Get flows assigned to a user within a specific tenant.
     *
     * @param  User  $user  The user to get flows for
     * @param  Tenant  $tenant  The tenant to scope flows to
     * @return LazyCollection<int, Flow> Lazy collection of flows
     */
    public function getForParticipant(User $user, Tenant $tenant): LazyCollection
    {
        $assignedFlows = Porter::getAssignedEntitiesByType(
            $user,
            Relation::getMorphAlias(Flow::class),
        );

        return $assignedFlows
            ->lazy()
            ->filter(fn (?Flow $flow) => $flow !== null && $flow->tenant_id === $tenant->id);
    }
}
