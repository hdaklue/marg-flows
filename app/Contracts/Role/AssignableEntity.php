<?php

declare(strict_types=1);

namespace App\Contracts\Role;

use App\Contracts\Tenant\HasActiveTenantContract;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

interface AssignableEntity extends HasActiveTenantContract
{
    /**
     * All role assignments this entity holds.
     */
    public function roleAssignments(): MorphMany;

    public function getAssignedEntitiesByType(string $type): Collection;

    /**
     * Get the morph class (used in model_type).
     */
    public function getMorphClass();

    /**
     * Unique identifier of the actor (model_id).
     */
    public function getKey();

    /**
     * Just for IDE Support.
     */
    public function notify($instance);
}
