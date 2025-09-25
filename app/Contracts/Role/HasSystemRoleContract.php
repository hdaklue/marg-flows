<?php

declare(strict_types=1);

namespace App\Contracts\Role;

use App\Models\Role;
use Illuminate\Support\Collection;

interface HasSystemRoleContract
{
    public function systemRoleByName(string $name): ?Role;

    public function getSystemRoles(): Collection;
}
