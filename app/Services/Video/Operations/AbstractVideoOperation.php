<?php

declare(strict_types=1);

namespace App\Services\Video\Operations;

use App\Services\Video\Contracts\VideoOperationContract;
use Closure;
use ProtoneMedia\LaravelFFMpeg\Exporters\MediaExporter;
use ProtoneMedia\LaravelFFMpeg\MediaOpener;

abstract class AbstractVideoOperation implements VideoOperationContract
{
    protected array $metadata = [];

    private int $executionIndex = 0;

    public function setExecutionIndex(int $index): void
    {
        $this->executionIndex = $index;
    }

    public function getPriority(): int
    {
        return $this->executionIndex; // Priority = execution order
    }

    public function canExecute(): bool
    {
        return true;
    }

    public function getMetadata(): array
    {
        return array_merge([
            'operation' => $this->getName(),
            'priority' => $this->getPriority(),
            'can_execute' => $this->canExecute(),
        ], $this->metadata);
    }

    /**
     * Handle method for Laravel Pipeline pattern.
     */
    public function handle(
        MediaExporter $mediaExporter,
        Closure $next,
    ): MediaExporter {
        if (!$this->canExecute()) {
            // Skip this operation and pass to next
            return $next($mediaExporter);
        }

        // Execute this operation
        $processedExporter = $this->execute($mediaExporter);

        // Pass result to next operation
        return $next($processedExporter);
    }

    abstract public function execute(MediaExporter $mediaExporter): MediaExporter;

    abstract public function getName(): string;

    abstract public function applyToBuilder(MediaExporter $builder): MediaExporter;

    abstract public function applyToMedia(MediaOpener $media);
}
