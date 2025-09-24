<?php

namespace App\Polishers;

use App\Models\User;
use Hdaklue\Polish\BasePolisher;

class UserPolisher extends BasePolisher
{
    public static function displayname(User $user): string
    {
        // Add your polishing logic here
        return str($user->name)->beforeLast(' ')->toString();
    }
}
