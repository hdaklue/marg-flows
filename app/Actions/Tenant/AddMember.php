<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Enums\Role\RoleEnum;
use App\Events\Tenant\TanantMemberAdded;
use App\Models\Flow;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Role\RoleCacheService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

class AddMember
{
    use AsAction;

    public function handle(Tenant $tenant, User $user, RoleEnum $role, array $flows, bool $silently = false)
    {
        try {
            DB::transaction(function () use ($tenant, $user, $role, $silently, $flows) {
                $tenant->addMember($user);
                setPermissionsTeamId($tenant->id);
                // RoleService::addParticipant()
                $tenant->addParticipant($user, $role->value, $silently);

                if (! empty($flows)) {
                    $roleId = $tenant->roles()->where('name', $role->value)->first()->id;
                    $this->assignFlowsRole($tenant, $user, $flows, $roleId);
                }

            });

            app(RoleCacheService::class)->invalidateEntityCache($tenant);

        } catch (\Exception $e) {
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

    private function assignFlowsRole(Tenant $tenant, User $user, $flows, $roleId)
    {
        $data = $this->buildInsertAttr($tenant, $user, $flows, $roleId);

        $roleableTypeKey = \config('permission.column_names.roleable_morph_type');
        DB::table(\config('permission.table_names.model_has_roles'))
            ->upsert(
                $data,
                [
                    'tenant_id',
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

    }

    private function buildInsertAttr(Tenant $tenant, User $user, array $flows, $roleId)
    {
        $rolableKey = \config('permission.column_names.roleable_morhp_key');
        $roleableTypeKey = \config('permission.column_names.roleable_morph_type');

        return collect($flows)->map(function ($flow) use ($rolableKey, $roleableTypeKey, $tenant, $user, $roleId) {
            return [
                'tenant_id' => $tenant->id,
                $rolableKey => $flow,
                $roleableTypeKey => Relation::getMorphAlias(Flow::class),
                'role_id' => $roleId,
                'model_id' => $user->id,
                'model_type' => Relation::getMorphAlias(User::class),
            ];
        })->toArray();
    }
}
