<?php

namespace App\Enums\Account;

enum AccountType: string
{
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case USER = 'user';

}
