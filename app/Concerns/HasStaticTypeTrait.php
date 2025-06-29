<?php

declare(strict_types=1);

namespace App\Concerns;

trait HasStaticTypeTrait
{
    public function getTypeName(): string
    {
        return str(static::class)->afterLast('\\')->title()->toString();
    }

    public function getTypeTitle(): string
    {
        // Throw if model has NEITHER title NOR name
        throw_unless(
            $this->hasAttribute('title') || $this->hasAttribute('name'),
            new \LogicException('Model must have either title or name attribute'),
        );

        return $this->title ?: $this->name;
    }
}
