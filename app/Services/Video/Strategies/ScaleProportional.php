<?php

declare(strict_types=1);

namespace App\Services\Video\Strategies;

use App\Services\Video\Contracts\ScaleStrategyContract;
use App\Services\Video\ValueObjects\Dimension;
use InvalidArgumentException;

final class ScaleProportional implements ScaleStrategyContract
{
    public function __construct(
        private readonly float $factor,
    ) {
        throw_if(
            $factor <= 0,
            new InvalidArgumentException('Scale factor must be positive'),
        );
    }

    public static function make(float $factor): self
    {
        return new self($factor);
    }

    public static function by(float $percentage): self
    {
        return new self($percentage / 100);
    }

    public function apply(Dimension $current, Dimension $target): Dimension
    {
        return $current->scaleByFactor($this->factor);
    }

    public function getDescription(): string
    {
        $percentage = round($this->factor * 100);

        return "Scale proportionally by {$percentage}%";
    }
}
