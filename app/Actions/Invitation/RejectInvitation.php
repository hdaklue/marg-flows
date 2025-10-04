<?php

declare(strict_types=1);

namespace App\Actions\Invitation;

use App\Models\MemberInvitation;
use App\Models\User;
use DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

final class RejectInvitation
{
    use AsAction;

    /**
     * @throws Throwable
     */
    public function handle(MemberInvitation $invitation, User $user): void
    {
        try {
            DB::transaction(function () use ($invitation) {
                $invitation->rejected_at = now();
                $invitation->save();
            });
        } catch (Throwable $e) {
            logger()->error('Error Accepting Invitation', [
                'invitation' => $invitation,
                'user' => $user,
                'error' => $e->getMessage(),
            ]);
            DB::rollBack();
            throw $e;
        }
    }
}
