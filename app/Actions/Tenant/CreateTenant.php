<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Enums\Role\RoleEnum;
use App\Events\Tenant\TenantCreated;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateTenant
{
    use AsAction;

    public function handle(array $data, User $user): void
    {

        $tenant = null;
        $participants = null;

        try {
            DB::transaction(function () use ($data, &$tenant, &$participants, $user) {
                $tenant = Tenant::make(['name' => $data['name']]);

                $tenant->creator()->associate($user);

                $tenant->save();

                $tenant->members()->attach(array_column($data['members'], 'name'));

                $participants = $this->getParticipants(array_column($data['members'], 'name'));

                setPermissionsTeamId($tenant->id);

                $systemRoles = $this->getSysyemRoles();

                $tenant->systemRoles()->createMany($systemRoles);

                $roles = collect($data['members'])->pluck('role', 'name')->toArray();

                $participants->each(fn ($participant): Tenant => $tenant->addParticipant($participant, roles: $roles[$participant->id], silently: true),
                );
            });

        } catch (\Exception $e) {
            Log::error('Tenant creation failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            throw $e; // Re-throw to let caller handle
        }

        if ($tenant && $participants) {
            TenantCreated::dispatch($tenant, $participants, $user);
        }

    }

    protected function getParticipants(array $ids): Collection
    {
        return User::whereIn('id', $ids)->get();
    }

    protected function getSysyemRoles(): array
    {
        return collect(RoleEnum::cases())->map(fn ($case) => [
            'name' => $case->value,
            'guard_name' => 'web',
        ])->toArray();
    }
}
