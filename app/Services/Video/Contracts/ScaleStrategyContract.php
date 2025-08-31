<?php

declare(strict_types=1);

namespace App\Services\Video\Contracts;

use App\Services\Video\ValueObjects\Dimension;

interface ScaleStrategyContract
{
    public function apply(Dimension $current, Dimension $target): Dimension;
    
    public function getDescription(): string;
}