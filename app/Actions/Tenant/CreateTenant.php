<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Contracts\Role\AssignableEntity;
use App\Enums\Role\RoleEnum;
use App\Events\Tenant\TenantCreated;
use App\Models\Tenant;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

final class CreateTenant
{
    use AsAction;

    // TODO:Should Receive CreateTenantDto
    public function handle(array $data, User $user): void
    {

        $tenant = null;
        $participants = null;

        try {
            DB::transaction(function () use ($data, &$tenant, &$participants, $user) {

                $tenant = new Tenant(['name' => $data['name']]);

                $tenant->creator()->associate($user);

                $tenant->save();

                $participants = $this->getParticipants(array_column($data['members'], 'name'));

                $systemRoles = $this->getSysyemRoles();

                $tenant->systemRoles()->createMany($systemRoles);

                $roles = collect($data['members'])->pluck('role', 'name')->toArray();

                $participants->each(fn (AssignableEntity $participant) => $tenant->addParticipant($participant, $roles[$participant->getKey()], silently: true),
                );
            });

        } catch (Exception $e) {
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
        ])->toArray();
    }
}
