<?php

declare(strict_types=1);

namespace App\Services\Video\Contracts;

use Closure;
use ProtoneMedia\LaravelFFMpeg\Exporters\MediaExporter;
use ProtoneMedia\LaravelFFMpeg\MediaOpener;

interface VideoOperationContract
{
    /**
     * Handle the video operation (Laravel Pipeline pattern).
     */
    public function handle(
        MediaExporter $mediaExporter,
        Closure $next,
    ): MediaExporter;

    /**
     * Execute the video operation.
     */
    public function execute(MediaExporter $mediaExporter): MediaExporter;

    /**
     * Get the operation name for debugging/logging.
     */
    public function getName(): string;

    /**
     * Get the operation priority (lower number = higher priority).
     */
    public function getPriority(): int;

    /**
     * Check if this operation can be executed with the given parameters.
     */
    public function canExecute(): bool;

    /**
     * Get operation metadata for debugging.
     */
    public function getMetadata(): array;

    /**
     * Set the execution index (order) for this operation.
     */
    public function setExecutionIndex(int $index): void;

    /**
     * Apply operation to Laravel FFMpeg builder pattern.
     */
    public function applyToBuilder(MediaExporter $builder): MediaExporter;

    /**
     * Apply filters to Media instance before export.
     */
    public function applyToMedia(MediaOpener $media);
}
