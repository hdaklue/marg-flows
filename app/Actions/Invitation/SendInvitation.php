<?php

declare(strict_types=1);

namespace App\Actions\Invitation;

use App\DTOs\Invitation\InvitationDTO;
use App\Models\MemberInvitation;
use App\Models\User;
use App\Notifications\Invitation\InvitationRecieved;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Lorisleiva\Actions\Concerns\AsAction;

class SendInvitation
{
    use AsAction;

    public function handle(InvitationDTO $dto)
    {
        $expires_at = now()->addDays(\config('member_invitation.expires_after_days'))->getTimestamp();

        // create the invitation
        $invitation = MemberInvitation::make([
            'email' => $dto->email,
            'expires_at' => $expires_at,
            'role_data' => $dto->role_data,
        ]);

        $invitation->sender()->associate($dto->sender->id);
        $invitation->save();

        // generate signed user
        $signedUrl = URL::temporarySignedRoute(
            'invitation.accept', $expires_at, ['token' => $invitation->id],
        );
        // send the email

        Notification::route('mail', $invitation->email)
            ->notify(new InvitationRecieved($signedUrl));
    }
}
