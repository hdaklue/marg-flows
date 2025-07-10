<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * A contract to standarize Notifications Text
 * maybe can be a DTO in the future.
 */
interface HasStaticType
{
    public function getTypeName(): string;

    public function getTypeTitle(): ?string;
}
