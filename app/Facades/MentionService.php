<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

final class MentionService extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'mention.service';
    }
}
