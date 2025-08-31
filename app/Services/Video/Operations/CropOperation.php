<?php

declare(strict_types=1);

namespace App\Services\Video\Operations;

use App\Services\Video\ValueObjects\Dimension;
use FFMpeg\Coordinate\Point;
use ProtoneMedia\LaravelFFMpeg\Exporters\MediaExporter;
use ProtoneMedia\LaravelFFMpeg\MediaOpener;

final class CropOperation extends AbstractVideoOperation
{
    public function __construct(
        private readonly int $x,
        private readonly int $y,
        private readonly Dimension $dimension,
    ) {
        $this->metadata = [
            'x' => $this->x,
            'y' => $this->y,
            'width' => $this->dimension->getWidth(),
            'height' => $this->dimension->getHeight(),
        ];
    }

    public function execute(MediaExporter $mediaExporter): MediaExporter
    {
        return $mediaExporter->addFilter(function ($filters) {
            $filters->crop(
                new Point($this->x, $this->y),
                new \FFMpeg\Coordinate\Dimension(
                    $this->dimension->getWidth(),
                    $this->dimension->getHeight(),
                ),
            );
        });
    }

    public function getName(): string
    {
        return 'crop';
    }

    public function canExecute(): bool
    {
        return $this->x >= 0 &&
               $this->y >= 0 &&
               $this->dimension->getWidth() > 0 &&
               $this->dimension->getHeight() > 0;
    }

    public function applyToBuilder(MediaExporter $builder): MediaExporter
    {
        return $builder->addFilter(function ($filters) {
            $filters->crop(
                new Point($this->x, $this->y),
                new \FFMpeg\Coordinate\Dimension(
                    $this->dimension->getWidth(),
                    $this->dimension->getHeight(),
                ),
            );
        });
    }

    public function applyToMedia(MediaOpener $media): MediaOpener
    {
        return $media->addFilter(function ($filters) {
            $filters->crop(
                new Point($this->x, $this->y),
                new \FFMpeg\Coordinate\Dimension(
                    $this->dimension->getWidth(),
                    $this->dimension->getHeight(),
                ),
            );
        });
    }
}
