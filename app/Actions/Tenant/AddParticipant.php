<?php

namespace App\Actions\Tenant;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

class AddParticipant
{
    use AsAction;

    public function handle(Tenant $tenant, User $user, string $role)
    {
        try {
            DB::transaction(function () use ($tenant, $user, $role) {
                $tenant->members()->attach($user);
                setPermissionsTeamId($tenant->id);
                $tenant->addParticipant($user, roles: $role);
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
    }
}
