<?php

declare(strict_types=1);

namespace App\Polishers;

use App\Models\User;
use Hdaklue\Polish\BasePolisher;
use Hdaklue\Porter\Contracts\RoleContract;

final class UserPolisher extends BasePolisher
{
    public static function polishUserName(User $user): string
    {
        // Add your polishing logic here
        return str($user->name)->beforeLast(' ')->toString();
    }

    public static function role(User $user, RoleContract $roleContract): string
    {
        return str($user->name)->beforeLast(' ')->toString() . $roleContract->getLabel();
    }
}
