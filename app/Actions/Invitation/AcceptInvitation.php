<?php

declare(strict_types=1);

namespace App\Actions\Invitation;

use App\Models\MemberInvitation;
use App\Models\User;
use DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

final class AcceptInvitation
{
    use AsAction;

    /**
     * @throws Throwable
     */
    public function handle(MemberInvitation $invitation, User $user): void
    {
        try {
            DB::transaction(function () use ($invitation, $user) {
                $invitation->accepted_at = now();
                $invitation->save();

                $invitation->loadMissing('tenant')->tenant->assign($user, $invitation->role_key);
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
