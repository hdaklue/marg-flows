<?php

declare(strict_types=1);

namespace App\ValueObjects\Deliverable;

use InvalidArgumentException;

final class DeliverableFormat
{
    protected string $name;

    protected string $description;

    protected string $configPath;

    public function __construct(private string $key)
    {
        if (! config('deliverables.formats')) {
            throw new InvalidArgumentException("Deliverable format '{$key}' does not exist.");
        }

        if (! array_key_exists($key, config('deliverables.formats'))) {
            throw new InvalidArgumentException("Deliverable format '{$key}' is not defined in the configuration.");
        }
        if (! config("deliverables.{$key}")) {
            throw new InvalidArgumentException("Deliverable format '{$key}' configuration path does not exist.");
        }
        $config = config('deliverables.formats')[$key];

        $this->name = $key;
        $this->configPath = "deliverables.{$key}";
        $this->name = $config['name'];
        $this->description = $config['description'];
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

    public function typesAsSelectArray(): array
    {
        $types = config($this->configPath);

        if (! $types) {
            return [];
        }

        return collect($types)->mapWithKeys(fn ($definition, $key) => [$key => $definition['name']])->toArray();
    }

    public function types(): array
    {
        $types = config($this->configPath);

        if (! $types) {
            return [];
        }

        return array_keys($types);
    }
}
