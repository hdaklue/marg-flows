<?php

declare(strict_types=1);

namespace App\Polishers;

use App\Models\MemberInvitation;
use Hdaklue\Polish\BasePolisher;

final class InvitationPolisher extends BasePolisher
{
    public static function status(MemberInvitation $invitation): string
    {
        if ($invitation->accepted()) {
            return 'accepted';
        }

        if ($invitation->rejected()) {
            return 'rejected';
        }

        return 'sent';
    }
}
