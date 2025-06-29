<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Events\Tenant\MemberRemoved;
use App\Models\Flow;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Role\RoleCacheService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

class RemoveMember
{
    use AsAction;

    public function handle(Tenant $tenant, User $user, User $by)
    {
        try {
            DB::transaction(function () use ($tenant, $user) {
                setPermissionsTeamId($tenant->getKey());
                $tenant->removeParticipant($user, silently: true);
                $tenant->removeMember($user);
                DB::table(\config('permission.table_names.model_has_roles'))
                    ->where('tenant_id', $tenant->getKey())
                    ->where('roleable_type', Relation::getMorphAlias(Flow::class))
                    ->where('model_id', $user->id)
                    ->delete();
                app(RoleCacheService::class)->invalidateEntityCache($tenant);
            });
        } catch (\Exception $exception) {
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
}
