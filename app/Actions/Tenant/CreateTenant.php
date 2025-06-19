<?php

namespace App\Actions\Tenant;

use App\Enums\Role\RoleEnum;
use App\Events\Tenant\TenantCreated;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateTenant
{
    use AsAction;

    public function handle(array $data)
    {

        $tenant = null;
        $participants = null;

        try {
            DB::transaction(function () use ($data, &$tenant, &$participants) {
                $tenant = Tenant::create(['name' => $data['name']]);

                $tenant->members()->attach(array_column($data['members'], 'name'));

                $participants = User::whereIn('id', array_column($data['members'], 'name'))->get();

                setPermissionsTeamId($tenant->id);

                $systemRoles = collect(RoleEnum::cases())->map(fn ($case) => [
                    'name' => $case->value,
                    'guard_name' => 'web',
                ])->toArray();

                $tenant->systemRoles()->createMany($systemRoles);

                $roles = collect($data['members'])->pluck('role', 'name')->toArray();

                $participants->each(fn ($participant): Tenant => $tenant->addParticipant($participant, roles: $roles[$participant->id], silently: true)
                );
            });

            if ($tenant && $participants) {
                TenantCreated::dispatch($tenant, $participants);
            }

        } catch (\Exception $e) {
            Log::error('Tenant creation failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            throw $e; // Re-throw to let caller handle
        }

    }
}
