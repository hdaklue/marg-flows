<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(): bool
    {
        if (auth()->user() && auth()->user()->canAccessAdmin()) {
            return true;
        }

        return false;
    }

    public function view(User $actor, User $mode): bool
    {

        return $actor->canAccessAdmin();
    }

    public function update(User $actor, User $model): bool
    {
        return $actor->isAppAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAppAdmin();

    }
}
