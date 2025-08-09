<?php

declare(strict_types=1);

namespace App\Services\Deliverable;

use App\Contracts\Deliverables\DeliverableContarct;
use App\Contracts\Deliverables\DeliverableSpecification;
use App\ValueObjects\Deliverable\DeliverableFormat;
use App\ValueObjects\Deliverable\DeliverableType;
use Carbon\Carbon;

final class Deliverable implements DeliverableContarct
{
    public function __construct(
        private string|array $owners,
        private string $name,
        private DeliverableFormat $format,
        private DeliverableType $type,
        private Carbon $successOn, private int $quantity = 1) {}

    public function getType(): DeliverableType
    {
        return $this->type;
    }

    public function getFormat(): DeliverableFormat
    {
        return $this->format;
    }

    public function getSpecification(): DeliverableSpecification
    {
        return $this->type->specification();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }
}
