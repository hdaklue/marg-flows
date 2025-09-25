<?php

declare(strict_types=1);

namespace App\Actions\Invitation;

use App\Actions\User\GenerateUserAvatar;
use App\DTOs\Invitation\InvitationDTO;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Notifications\Invitation\InvitationRecieved;

use function bcrypt;
use function config;

use Exception;
use Hdaklue\MargRbac\Facades\RoleManager;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

use function Illuminate\Support\defer;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

use function request;

final class InviteMember
{
    use AsAction;

    protected $member = null;

    protected $sender;

    protected $password;

    protected $encryptedPassword;

    public function handle(InvitationDTO $dto)
    {
        $this->generatePassword();
        try {
            DB::transaction(function () use ($dto) {
                $this->persistMember($dto);
                // $this->attachMemberToTenant($dto);
                $this->assingMemberRoles($dto);
            });
        } catch (Exception $e) {
            throw $e;
        }

        $this->notifyMember();

        CreateInvitation::run(request()->user(), $this->member, $dto->role_data->toArray());
    }

    private function notifyMember()
    {
        $this->member->notify(new InvitationRecieved($this->password));
    }

    private function persistMember(InvitationDTO $dto)
    {
        $this->member = User::create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => $this->password,
            'timezone' => $dto->timezone,
        ]);

        defer(fn () => GenerateUserAvatar::run($this->member));
    }

    private function generatePassword(): string
    {
        $this->password = Str::password(10, true, true, false);

        return $this->encryptedPassword = bcrypt($this->password);
    }

    private function assingMemberRoles(InvitationDTO $dto)
    {
        $tanentIds = $dto->role_data->pluck('id')->toArray();
        $tenants = Tenant::whereIn('id', $tanentIds)->get();

        DB::table(config('role.table_names.model_has_roles'))->insert($this->prepareInsertAttr(
            $dto->role_data,
            $this->member,
        ));
        RoleManager::bulkClearCache($tenants);
    }

    // private function attachMemberToTenant(InvitationDTO $dto)
    // {
    //     $data = collect($dto->role_data)->map(function ($role) {
    //         return [
    //             'tenant_id' => $role['tenant_id'],
    //             'user_id' => $this->member->id,
    //         ];
    //     })->toArray();

    //     TenantUser::insert($data);
    // }

    private function prepareInsertAttr(Collection $roles, User $invitedMember): array
    {
        return $roles->map(function ($role) use ($invitedMember) {
            return [
                'roleable_id' => $role['tenant_id'],
                'roleable_type' => Relation::getMorphAlias(Tenant::class),
                'role_id' => $role['role_id'],
                'model_id' => $invitedMember->getKey(),
                'model_type' => $invitedMember->getMorphClass(),
            ];
        })->toArray();
    }
}
