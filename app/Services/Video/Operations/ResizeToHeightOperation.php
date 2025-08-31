<?php

declare(strict_types=1);

namespace App\Services\Video\Operations;

use ProtoneMedia\LaravelFFMpeg\Exporters\MediaExporter;

class ResizeToHeightOperation extends AbstractVideoOperation
{

    public function __construct(
        private readonly int $height
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
                new \FFMpeg\Coordinate\Dimension(-1, $this->height), // -1 means maintain aspect ratio
                'fit'
            );
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
                new \FFMpeg\Coordinate\Dimension(-1, $this->height), // -1 means maintain aspect ratio
                'fit'
            );
        });
    }

    public function applyToMedia(\ProtoneMedia\LaravelFFMpeg\MediaOpener $media): \ProtoneMedia\LaravelFFMpeg\MediaOpener
    {
        return $media->addFilter(function ($filters) {
            $filters->resize(
                new \FFMpeg\Coordinate\Dimension(-1, $this->height), // -1 means maintain aspect ratio
                'fit'
            );
        });
    }
}