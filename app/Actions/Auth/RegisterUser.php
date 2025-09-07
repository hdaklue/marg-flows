<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\User\GetUserLocation;
use App\Models\Tenant;
use App\Models\User;
use Exception;
use Filament\Auth\Events\Registered;
use Hdaklue\MargRbac\Enums\Role\RoleEnum;
use Hdaklue\Porter\RoleFactory;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

final class RegisterUser
{
    use AsAction;

    public function handle(
        string $email,
        string $username,
        string $password,
    ): null|User {
        $ip = app()->isProduction() ? request()->ip() : '41.43.60.242';
        $timezone = GetUserLocation::run($ip)->timezone;

        try {
            return DB::transaction(function () use (
                $username,
                $email,
                $password,
                $timezone,
            ) {
                $user = new User();

                $user->username = $username;
                $user->email = $email;
                $user->password = $password;
                $user->name = $username;
                $user->save();

                $user->refresh();

                $this->createUserProfile($user, $timezone);

                $createdTenant = $this->createDefaultTenant($user);

                $user->switchActiveTenant($createdTenant);
                event(new Registered($user));

                return $user;
            });
        } catch (Exception $e) {
            logger()->error($e->getMessage());
            throw $e;
        }
    }

    private function createUserProfile(User $user, $timezone)
    {
        $user->profile()->create([
            'timezone' => $timezone,
        ]);
    }

    private function createDefaultTenant(User $user): Tenant
    {
        $tenantName = str($user->getAttribute('username'))
            ->lower()
            ->explode(' ')
            ->first() . ' team';
        $tenant = new Tenant(['name' => $tenantName]);
        $tenant->creator()->associate($user);
        $tenant->save();

        $tenant->assign($user, RoleFactory::admin());

        $tenant->refresh();

        return $tenant;
    }

    // protected function getSystemRoles(): array
    // {
    //     return collect(RoleEnum::cases())->map(fn ($case) => [
    //         'name' => $case->value,
    //     ])->toArray();
    // }
}
