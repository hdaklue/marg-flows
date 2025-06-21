<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Enums\Role\RoleEnum;
use App\Events\Tenant\MemberAdded;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

class AddMember
{
    use AsAction;

    public function handle(Tenant $tenant, User $user, RoleEnum $role, bool $silently = false)
    {
        try {
            DB::transaction(function () use ($tenant, $user, $role, $silently) {
                $tenant->addMember($user);
                setPermissionsTeamId($tenant->id);
                $tenant->addParticipant($user, $role->value, $silently);
            });

        } catch (\Exception $e) {
            Log::error('Tenant creation failed', [
                'error' => $e->getMessage(),
                'data' => [
                    'tenant' => $tenant,
                    'user' => $user,
                    'role' => $role,
                ],
            ]);
            throw $e; // Re-throw to let caller handle
        }

        MemberAdded::dispatch($tenant, $user, $role);
    }
}
