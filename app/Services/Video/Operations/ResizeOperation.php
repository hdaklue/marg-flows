<?php

declare(strict_types=1);

namespace App\Services\Video\Operations;

use App\Services\Video\ValueObjects\Dimension;
use ProtoneMedia\LaravelFFMpeg\Exporters\MediaExporter;

class ResizeOperation extends AbstractVideoOperation
{

    public function __construct(
        private readonly Dimension $dimension,
        private readonly string $mode = 'fit'
    ) {
        $this->metadata = [
            'width' => $this->dimension->getWidth(),
            'height' => $this->dimension->getHeight(),
            'mode' => $this->mode,
        ];
    }

    public function execute(MediaExporter $mediaExporter): MediaExporter
    {
        // Use addFilter instead of direct resize to maintain MediaExporter chain
        return $mediaExporter->addFilter(function ($filters) {
            $filters->resize(
                new \FFMpeg\Coordinate\Dimension(
                    $this->dimension->getWidth(),
                    $this->dimension->getHeight()
                ),
                $this->mode
            );
        });
    }

    public function getName(): string
    {
        return 'resize';
    }

    public function canExecute(): bool
    {
        return $this->dimension->getWidth() > 0 && $this->dimension->getHeight() > 0;
    }
}