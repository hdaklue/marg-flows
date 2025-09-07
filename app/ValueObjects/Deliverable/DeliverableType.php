<?php

declare(strict_types=1);

namespace App\ValueObjects\Deliverable;

use App\Contracts\Deliverables\DeliverableSpecification;
use App\Services\Deliverable\DeliverableSpecResolver;
use InvalidArgumentException;

final class DeliverableType
{
    private string $configPath;

    private string $name;

    private string $description;

    public function __construct(
        private DeliverableFormat $format,
        private string $key,
    ) {
        throw_unless(
            array_key_exists($key, config($format->configPath())),
            new InvalidArgumentException(
                "Deliverable type '{$key}' does not exist.",
            ),
        );

        $this->configPath = "{$format->configPath()}.{$key}";
        $this->name = config("{$this->configPath}.name");
        $this->description = config("{$this->configPath}.description");
    }

    public function specification(): DeliverableSpecification
    {
        return DeliverableSpecResolver::resolve(
            format: $this->format,
            type: $this,
        );
    }

    public function name(): string
    {
        return $this->name;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function configPath(): string
    {
        return $this->configPath;
    }
}
