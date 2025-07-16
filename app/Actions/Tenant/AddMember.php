<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Contracts\Role\RoleableEntity;
use App\Enums\Role\RoleEnum;
use App\Events\Tenant\TanantMemberAdded;
use App\Facades\RoleManager;
use App\Models\Flow;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Collection;

use function config;

use Exception;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

final class AddMember
{
    use AsAction;

    public function handle(Tenant $tenant, User $user, RoleEnum $role, array $flows, bool $silently = false)
    {
        try {
            $assignedRole = $tenant->systemRoleByName($role);

            DB::transaction(function () use ($tenant, $user, $assignedRole, $silently, $flows) {

                $tenant->addParticipant($user, $assignedRole->name, $silently);

                if (! empty($flows)) {

                    $this->assignFlowsRole($tenant, $user, $flows, $assignedRole);
                }

            });

        } catch (Exception $e) {
            Log::error('Adding Member to Tenant Failed', [
                'error' => $e->getMessage(),
                'data' => [
                    'tenant' => $tenant,
                    'user' => $user,
                    'role' => $role,
                ],
            ]);
            throw $e; // Re-throw to let caller handle
        }

        TanantMemberAdded::dispatch($tenant, $user, $role);
    }

    private function assignFlowsRole(Tenant $tenant, User $user, $flows, Role $role)
    {
        $flows = $tenant->flows()->whereIn('id', $flows)->get();
        if ($flows->isEmpty()) {
            return;
        }

        $data = $this->buildInsertAttr($tenant, $user, $flows->pluck('id')->toArray(), $role);

        DB::table(config('role.table_names.model_has_roles'))
            ->upsert(
                $data,
                [
                    'roleable_type',
                    'roleable_id',
                    'role_id',
                    'model_id',
                    'model_type',
                ], [
                    'roleable_type',
                    'roleable_id',
                    'role_id',
                ],
            );
        /** @var Collection<int, RoleableEntity> $flows */
        RoleManager::bulkClearCache($flows->collect());
    }

    private function buildInsertAttr(Tenant $tenant, User $user, array $flows, Role $assignedRole)
    {
        $rolableKey = config('role.column_names.roleable_morph_key');
        $roleableTypeKey = config('role.column_names.roleable_morph_type');

        return collect($flows)->map(function ($flow) use ($rolableKey, $roleableTypeKey, $user, $assignedRole) {
            return [
                $rolableKey => $flow,
                $roleableTypeKey => Relation::getMorphAlias(Flow::class),
                'role_id' => $assignedRole->getKey(),
                'model_id' => $user->id,
                'model_type' => Relation::getMorphAlias(User::class),
            ];
        })->toArray();
    }
}
