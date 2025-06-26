<?php

declare(strict_types=1);

namespace App\Actions\Invitation;

use App\Models\MemberInvitation;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateInvitation
{
    use AsAction;

    public function handle(User $sender, User $receiver, array $role_data)
    {

        $invitation = MemberInvitation::make([
            'role_data' => $role_data,
        ]);

        $invitation->sender()->associate($sender);
        $invitation->receiver()->associate($receiver);
        $invitation->save();

    }
}
