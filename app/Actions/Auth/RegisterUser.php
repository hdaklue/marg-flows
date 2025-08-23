<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\User\GetUserLocation;
use App\Enums\Role\RoleEnum;
use App\Models\Tenant;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

final class RegisterUser
{
    use AsAction;

    public function handle(string $email, string $name, string $password): ?User
    {
        $ip = app()->isProduction() ? request()->ip() : '41.43.60.242';
        $timezone = GetUserLocation::run($ip)->timezone;
        $tenantName = str($name)->lower()->explode(' ')->first() . ' team';

        try {
            return DB::transaction(function () use ($name, $email, $password, $timezone, $tenantName) {
                $user = new User;

                $user->name = $name;
                $user->email = $email;
                $user->password = $password;
                $user->timezone = $timezone;

                $user->save();

                $tenant = new Tenant(['name' => $tenantName]);
                $tenant->creator()->associate($user);
                $tenant->save();
                $tenant->systemRoles()->createMany($this->getSystemRoles());

                $tenant->addParticipant($user, RoleEnum::ADMIN, true);

                $user->switchActiveTenant($tenant);

                return $user;
            });

        } catch (Exception $e) {
            logger()->error($e->getMessage());
            throw $e;
        }
    }

    protected function getSystemRoles(): array
    {
        return collect(RoleEnum::cases())->map(fn ($case) => [
            'name' => $case->value,
        ])->toArray();
    }
}
