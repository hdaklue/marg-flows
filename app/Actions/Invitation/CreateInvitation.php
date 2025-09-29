<?php

declare(strict_types=1);

namespace App\Actions\Invitation;

use App\Models\MemberInvitation;
use App\Models\Tenant;
use App\Models\User;
use Hdaklue\Porter\Contracts\RoleContract;
use Lorisleiva\Actions\Concerns\AsAction;

final class CreateInvitation
{
    use AsAction;

    public function handle(User $sender, Tenant $tenant, string $receiver_email, RoleContract $role): MemberInvitation
    {
        $invitation = new MemberInvitation([
            'role_key' => $role,
            'receiver_email' => $receiver_email,
            'expires_at' => now()->addDays(10),
        ]);

        $invitation->sender()->associate($sender);
        $invitation->tenant()->associate($tenant);
        $invitation->save();

        return $invitation;
    }
}
