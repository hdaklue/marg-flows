<?php

declare(strict_types=1);

namespace App\Actions\Invitation;

use App\Models\MemberInvitation;
use Lorisleiva\Actions\Concerns\AsAction;

final class RevokeInvitaion
{
    use AsAction;

    public function handle(string $invitationId): void
    {
        MemberInvitation::query()
            ->where('id', $invitationId)
            ->lockForUpdate()
            ->delete();
    }
}
