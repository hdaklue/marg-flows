<?php

declare(strict_types=1);

namespace App\Services\Video\Operations;

use InvalidArgumentException;
use ProtoneMedia\LaravelFFMpeg\Exporters\MediaExporter;

class WatermarkOperation extends AbstractVideoOperation
{

    public function __construct(
        private readonly string $watermarkPath,
        private readonly string $position = 'bottom-right',
        private readonly float $opacity = 1.0
    ) {
        $this->metadata = [
            'watermark_path' => $this->watermarkPath,
            'position' => $this->position,
            'opacity' => $this->opacity,
        ];
    }

    public function execute(MediaExporter $mediaExporter): MediaExporter
    {
        return $mediaExporter->addFilter(function ($filters) {
            $filters->watermark($this->watermarkPath, [
                'position' => $this->position,
                'opacity' => $this->opacity
            ]);
        });
    }

    public function getName(): string
    {
        return 'watermark';
    }

    public function canExecute(): bool
    {
        if (!file_exists($this->watermarkPath)) {
            return false;
        }

        if ($this->opacity < 0 || $this->opacity > 1) {
            return false;
        }

        return true;
    }
}