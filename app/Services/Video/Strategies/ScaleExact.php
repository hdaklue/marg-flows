<?php

declare(strict_types=1);

namespace App\Services\Video\Strategies;

use App\Services\Video\Contracts\ScaleStrategyContract;
use App\Services\Video\ValueObjects\Dimension;

class ScaleExact implements ScaleStrategyContract
{
    public function __construct(
        private readonly Dimension $target,
    ) {}

    public function apply(Dimension $current, Dimension $target): Dimension
    {
        return $this->target;
    }

    public function getDescription(): string
    {
        return "Scale to exactly {$this->target} (ignoring aspect ratio)";
    }

    public static function make(Dimension $target): self
    {
        return new self($target);
    }
}
