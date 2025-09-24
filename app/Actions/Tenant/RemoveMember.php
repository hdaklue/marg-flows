<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Events\Tenant\MemberRemoved;
use App\Models\Flow;
use App\Models\Tenant;
use App\Models\User;
use Exception;
use Hdaklue\MargRbac\Contracts\Role\AssignableEntity;
use Hdaklue\MargRbac\Contracts\Role\RoleableEntity;
use Hdaklue\MargRbac\Facades\RoleManager;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

use function config;

final class RemoveMember
{
    use AsAction;

    public function handle(Tenant $tenant, AssignableEntity $user, User $by)
    {
        try {
            DB::transaction(function () use ($tenant, $user) {
                $tenant->removeParticipant($user, silently: true);
                if ($tenant->getKey() === $user->getActiveTenantId()) {
                    $user->clearActiveTenant();
                }
                $this->revokeAssigmentsOnTanatFlow($user, $tenant);
            });
        } catch (Exception $exception) {
            Log::critical('Tenant removal failed', [
                'error' => $exception->getMessage(),
                'data' => [
                    'tenant' => $tenant,
                    'user' => $user,
                ],
            ]);
            throw $exception;
        }

        MemberRemoved::dispatch($tenant, $user, $by);
    }

    public function revokeAssigmentsOnTanatFlow(AssignableEntity $entity, RoleableEntity $target)
    {
        $flows = $target->loadMissing('flows')->flows;

        if ($flows->isEmpty()) {
            return; // Nothing to revoke
        }

        DB::table(config('role.table_names.model_has_roles'))
            ->where('roleable_type', Relation::getMorphAlias(Flow::class))
            ->where('model_type', $entity->getMorphClass())
            ->where('model_id', $entity->getKey())
            ->whereIn('roleable_id', $flows->pluck('id')->toArray())
            ->delete();

        RoleManager::bulkClearCache($flows);
    }
}
