<?php

declare(strict_types=1);

namespace App\Contracts;

interface ScopedToTenant
{
    public function getTenantId(): string|int;
}
