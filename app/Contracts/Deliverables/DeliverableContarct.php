<?php

declare(strict_types=1);

namespace App\Contracts\Deliverables;

use App\ValueObjects\Deliverable\DeliverableFormat;
use App\ValueObjects\Deliverable\DeliverableType;

interface DeliverableContarct
{
    public function getType(): DeliverableType;

    public function getFormat(): DeliverableFormat;

    public function getSpecification(): DeliverableSpecification;

    public function getName(): string;

    public function getQuantity(): int;
}
