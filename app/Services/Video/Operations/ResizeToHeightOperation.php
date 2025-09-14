<?php

declare(strict_types=1);

namespace App\Services\Video\Operations;

use FFMpeg\Coordinate\Dimension;
use ProtoneMedia\LaravelFFMpeg\Exporters\MediaExporter;
use ProtoneMedia\LaravelFFMpeg\MediaOpener;

final class ResizeToHeightOperation extends AbstractVideoOperation
{
    public function __construct(
        private readonly int $height,
    ) {
        $this->metadata = [
            'height' => $this->height,
            'mode' => 'height_fit',
        ];
    }

    public function execute(MediaExporter $mediaExporter): MediaExporter
    {
        // Use addFilter with scale that maintains aspect ratio based on height
        return $mediaExporter->addFilter(function ($filters) {
            $filters->resize(
                new Dimension(-1, $this->height),
                'fit',
            ); // -1 means maintain aspect ratio
        });
    }

    public function getName(): string
    {
        return 'resize_to_height';
    }

    public function canExecute(): bool
    {
        return $this->height > 0;
    }

    public function applyToBuilder(MediaExporter $builder): MediaExporter
    {
        return $builder->addFilter(function ($filters) {
            $filters->resize(
                new Dimension(-1, $this->height),
                'fit',
            ); // -1 means maintain aspect ratio
        });
    }

    public function applyToMedia(MediaOpener $media): MediaOpener
    {
        return $media->addFilter(function ($filters) {
            $filters->resize(
                new Dimension(-1, $this->height),
                'fit',
            ); // -1 means maintain aspect ratio
        });
    }
}
