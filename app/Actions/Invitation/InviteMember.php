<?php

declare(strict_types=1);

namespace App\Actions\Invitation;

use App\DTOs\Invitation\InvitationDTO;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Notifications\Invitation\InvitationRecieved;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

class InviteMember
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

        } catch (\Exception $e) {
            throw $e;
        }

        $this->notifyMember();

        CreateInvitation::run(\request()->user(), $this->member, $dto->role_data);
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
    }

    private function generatePassword(): string
    {
        $this->password = Str::password(8);

        return $this->encryptedPassword = \bcrypt($this->password);
    }

    private function assingMemberRoles(InvitationDTO $dto)
    {

        DB::table(\config('permission.table_names.model_has_roles'))
            ->insert($this->prepareInsertAttr($dto->role_data, $this->member));
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

    private function prepareInsertAttr(array $roles, User $invitedMember): array
    {

        return collect($roles)->map(function ($role) use ($invitedMember) {
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
