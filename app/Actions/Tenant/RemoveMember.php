<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Events\Tenant\MemberRemoved;
use App\Models\Tenant;
use App\Models\User;
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
                setPermissionsTeamId($tenant->id);
                $tenant->removeParticipant($user, silently: true);
                $tenant->removeMember($user);
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
