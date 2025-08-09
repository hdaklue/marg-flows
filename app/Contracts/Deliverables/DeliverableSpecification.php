<?php

declare(strict_types=1);

namespace App\Contracts\Deliverables;

use JsonSerializable;

/**
 * Base interface for deliverable specifications.
 */
interface DeliverableSpecification extends JsonSerializable
{
    public function getAspectRatio(): float;

    public function getAspectRatioName(): string;

    public function toArray(): array;

    public function validate(array $fileData): bool;

    public function getRequirements(): array;
}
