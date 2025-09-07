<?php

declare(strict_types=1);

namespace App\Services\Deliverable;

use App\Contracts\Deliverables\DeliverableServiceContract;
use App\ValueObjects\Deliverable\DeliverableFormat;
use App\ValueObjects\Deliverable\DeliverableType;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

final class DesignDeliverableService implements DeliverableServiceContract
{
    private const string FORMAT = 'design';

    public Carbon $successOn;

    public DeliverableType $typeObject;

    public DeliverableFormat $formatObject;

    public int $quantity = 1;

    public string $name;

    public array $assignedUsers = [];

    public function type(string $type): self
    {
        $this->formatObject = new DeliverableFormat(self::FORMAT);
        $this->typeObject = new DeliverableType($this->formatObject, $type);

        return $this;
    }

    public function quantity(int $quantity): self
    {
        throw_if(
            $quantity <= 0,
            new InvalidArgumentException('Quantity must be greater than zero.'),
        );
        $this->quantity = $quantity;

        return $this;
    }

    public function successOn(Carbon $date): self
    {
        throw_if(
            $date->isPast(),
            new InvalidArgumentException('Success date must be in the future.'),
        );
        $this->successOn = $date;

        return $this;
    }

    public function name(string $name): self
    {
        throw_if(
            empty($name),
            new InvalidArgumentException('Name cannot be empty.'),
        );
        $this->name = $name;

        return $this;
    }

    public function assignedTo(string|array $users): self
    {
        // Assuming this method is for assigning users, implementation can vary.
        // Here we just store the users in a property for demonstration.
        if (is_string($users)) {
            $users = [$users];
        }
        throw_if(
            !is_array($users) || empty($users),
            new InvalidArgumentException(
                'Assigned users must be a non-empty array or string.',
            ),
        );
        $this->assignedUsers = $users;

        return $this;
    }

    public function build(): Deliverable
    {
        throw_if(
            !isset($this->formatObject)
            || !isset($this->typeObject)
            || !isset($this->deliverableSpecification),
            new InvalidArgumentException(
                'Deliverable type must be set before building.',
            ),
        );

        return new Deliverable(
            owners: $this->assignedUsers,
            format: $this->formatObject,
            type: $this->typeObject,
            name: $this->name,
            quantity: $this->quantity,
            successOn: $this->successOn,
        );
    }
}
