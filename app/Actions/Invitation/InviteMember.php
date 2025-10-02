<?php

declare(strict_types=1);

namespace App\Actions\Invitation;

use App\Actions\User\GenerateUserAvatar;
use App\DTOs\Invitation\InvitationDTO;
use App\Models\MemberInvitation;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Notifications\Invitation\InvitationRecieved;

use function bcrypt;
use function config;

use Hdaklue\Porter\RoleFactory;
use Hdaklue\Porter\RoleManager;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

use function Illuminate\Support\defer;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

final class InviteMember
{
    use AsAction;

    protected User $sender;

    protected string $password;

    protected string $encryptedPassword;

    /**
     * @throws Throwable
     */
    public function handle(User $sender, Tenant $tenant, string $role, string $receiver_email): void
    {
        // create the invitation
        // send an email with the invitation link

        $role = RoleFactory::tryMake($role);
        $invitation = CreateInvitation::run($sender, $tenant, $receiver_email, $role);
        $this->notifyMember($tenant, $invitation);

    }

    private function notifyMember(Tenant $tenant, MemberInvitation $invitation): void
    {
        $receiver = new User;
        $receiver->email = $invitation->getAttribute('receiver_email');
        //        $receiver->notify(new InvitationRecieved($tenant, $invitation->getKey()));
        Notification::route('mail', [$invitation->getAttribute('receiver_email') => $invitation->getAttribute('receiver_email')])
            ->notify(new InvitationRecieved($tenant, $invitation->getKey()));

    }

    //    private function persistMember(InvitationDTO $dto)
    //    {
    //        $this->member = User::create([
    //            'name' => $dto->name,
    //            'email' => $dto->email,
    //            'password' => $this->password,
    //            'timezone' => $dto->timezone,
    //        ]);
    //
    //        defer(fn () => GenerateUserAvatar::run($this->member));
    //    }

    //    private function generatePassword(): string
    //    {
    //        $this->password = Str::password(10, true, true, false);
    //
    //        return $this->encryptedPassword = bcrypt($this->password);
    //    }

    //    private function assingMemberRoles(InvitationDTO $dto): void
    //    {
    //        $tanentIds = $dto->role_data->pluck('id')->toArray();
    //        $tenants = Tenant::whereIn('id', $tanentIds)->get();
    //
    //        DB::table(config('role.table_names.model_has_roles'))->insert($this->prepareInsertAttr(
    //            $dto->role_data,
    //            $this->member,
    //        ));
    //        RoleManager::bulkClearCache($tenants);
    //    }

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

    //    private function prepareInsertAttr(Collection $roles, User $invitedMember): array
    //    {
    //        return $roles->map(function ($role) use ($invitedMember) {
    //            return [
    //                'roleable_id' => $role['tenant_id'],
    //                'roleable_type' => Relation::getMorphAlias(Tenant::class),
    //                'role_id' => $role['role_id'],
    //                'model_id' => $invitedMember->getKey(),
    //                'model_type' => $invitedMember->getMorphClass(),
    //            ];
    //        })->toArray();
    //    }
}
