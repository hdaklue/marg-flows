<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Support\Str;
use LogicException;

trait SentInNotificationTrait
{
    public function getTypeForNotification(): string
    {
        return Str::of(static::class)
            ->afterLast('\\')
            ->title()
            ->toString();
    }

    public function getNameForNotification(): string
    {
        if ($this->hasAttribute('title')) {
            return $this->getAttribute('title');
        }

        if ($this->hasAttribute('name')) {
            return $this->getAttribute('name');
        }
        throw new LogicException("Model [{get_class({$this})] must have either 'title' or 'name' attribute.");
    }
}
