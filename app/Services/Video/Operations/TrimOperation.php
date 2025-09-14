<?php

declare(strict_types=1);

namespace App\Services\Video\Operations;

use FFMpeg\Coordinate\TimeCode;
use ProtoneMedia\LaravelFFMpeg\Exporters\MediaExporter;
use ProtoneMedia\LaravelFFMpeg\MediaOpener;

final class TrimOperation extends AbstractVideoOperation
{
    public function __construct(
        private readonly float $start,
        private readonly float $duration,
    ) {
        $this->metadata = [
            'start' => $this->start,
            'duration' => $this->duration,
            'end' => $this->start + $this->duration,
        ];
    }

    public function execute(MediaExporter $mediaExporter): MediaExporter
    {
        // Use Laravel FFMpeg's clip functionality
        return $mediaExporter->addFilter(function ($filters) {
            $filters->clip(
                TimeCode::fromSeconds($this->start),
                TimeCode::fromSeconds($this->duration),
            );
        });
    }

    public function getName(): string
    {
        return 'trim';
    }

    public function canExecute(): bool
    {
        return $this->start >= 0 && $this->duration > 0;
    }

    public function applyToBuilder(MediaExporter $builder): MediaExporter
    {
        return $builder->addFilter(function ($filters) {
            $filters->clip(
                TimeCode::fromSeconds($this->start),
                TimeCode::fromSeconds($this->duration),
            );
        });
    }

    public function applyToMedia(MediaOpener $media): MediaOpener
    {
        return $media->addFilter(function ($filters) {
            $filters->clip(
                TimeCode::fromSeconds($this->start),
                TimeCode::fromSeconds($this->duration),
            );
        });
    }
}
