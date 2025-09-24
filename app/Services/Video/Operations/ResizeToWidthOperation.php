<?php

declare(strict_types=1);

namespace App\Services\Video\Operations;

use FFMpeg\Coordinate\Dimension;
use ProtoneMedia\LaravelFFMpeg\Exporters\MediaExporter;
use ProtoneMedia\LaravelFFMpeg\MediaOpener;

final class ResizeToWidthOperation extends AbstractVideoOperation
{
    public function __construct(
        private readonly int $width,
    ) {
        $this->metadata = [
            'width' => $this->width,
            'mode' => 'width_fit',
        ];
    }

    public function execute(MediaExporter $mediaExporter): MediaExporter
    {
        // Use addFilter with scale that maintains aspect ratio based on width
        return $mediaExporter->addFilter(function ($filters) {
            $filters->resize(new Dimension($this->width, -1), 'fit'); // -1 means maintain aspect ratio
        });
    }

    public function getName(): string
    {
        return 'resize_to_width';
    }

    public function canExecute(): bool
    {
        return $this->width > 0;
    }

    public function applyToBuilder(MediaExporter $builder): MediaExporter
    {
        return $builder->addFilter(function ($filters) {
            $filters->resize(new Dimension($this->width, -1), 'fit'); // -1 means maintain aspect ratio
        });
    }

    public function applyToMedia(MediaOpener $media): MediaOpener
    {
        return $media->addFilter(function ($filters) {
            $filters->resize(new Dimension($this->width, -1), 'fit'); // -1 means maintain aspect ratio
        });
    }
}
