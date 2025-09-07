<?php

declare(strict_types=1);

namespace App\Contracts;

interface SentInNotification
{
    public function getTypeForNotification(): string;

    public function getNameForNotification(): string;
}
