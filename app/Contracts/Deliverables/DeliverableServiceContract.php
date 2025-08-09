<?php

declare(strict_types=1);

namespace App\Contracts\Deliverables;

use Illuminate\Support\Carbon;

interface DeliverableServiceContract
{
    public function type(string $type): self;

    public function quantity(int $quantity): self;

    public function successOn(Carbon $date): self;

    public function name(string $name): self;

    public function assignedTo(string|array $users): self;

    public function build(): DeliverableContarct;
}
