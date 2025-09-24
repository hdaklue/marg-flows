<?php

declare(strict_types=1);

namespace App\Services\Video\Strategies;

use App\Services\Video\Contracts\ScaleStrategyContract;
use App\Services\Video\ValueObjects\Dimension;

final class ScaleToFit implements ScaleStrategyContract
{
    public function __construct(
        private readonly Dimension $target,
    ) {}

    public static function make(Dimension $target): self
    {
        return new self($target);
    }

    public function apply(Dimension $current, Dimension $target): Dimension
    {
        return $current->scaleTo($this->target->getWidth(), $this->target->getHeight(), true);
    }

    public function getDescription(): string
    {
        return "Scale to fit within {$this->target} maintaining aspect ratio";
    }
}
