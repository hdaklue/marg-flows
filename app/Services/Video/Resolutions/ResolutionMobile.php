<?php

declare(strict_types=1);

namespace App\Services\Video\Resolutions;

use App\Services\Video\ValueObjects\Resolution;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Filters\Video\ResizeFilter;
use ProtoneMedia\LaravelFFMpeg\Exporters\MediaExporter;

final class ResolutionMobile extends AbstractResolution
{
    protected string $format = 'mp4';

    protected string $quality = 'medium';

    protected bool $allowScaleUp = true; // Mobile often needs upscaling from small sources

    protected Resolution $resolution;

    public function __construct(?Resolution $resolution = null, bool $allowScaleUp = true)
    {
        $this->resolution = $resolution ?? Resolution::createMobilePortrait(); // Portrait by default
        $this->dimension = $this->resolution->dimension;
        $this->bitrate = $this->resolution->getBitrateKbps();
        $this->allowScaleUp = $allowScaleUp;

        // Set mobile-friendly constraints
        $this->maxDimension = \App\Services\Video\ValueObjects\Dimension::from(1080, 1920);
        $this->minDimension = \App\Services\Video\ValueObjects\Dimension::from(480, 640);
    }

    public static function landscape(): self
    {
        return new self(Resolution::createMobileLandscape());
    }

    public static function portrait(): self
    {
        return new self(Resolution::createMobilePortrait());
    }

    public static function square(): self
    {
        return new self(Resolution::createMobileSquare());
    }

    public function apply(MediaExporter $exporter): void
    {
        // Use addFilter approach as recommended by Laravel FFMpeg
        $exporter->addFilter(function ($filters) {
            $filters->resize(
                new Dimension($this->dimension->getWidth(), $this->dimension->getHeight()),
                $this->resizeMode,
            );
        });
    }

    public function getFilter()
    {
        return new ResizeFilter(
            new Dimension($this->dimension->getWidth(), $this->dimension->getHeight()),
            $this->resizeMode,
            false  // Disable standard aspect ratio enforcement to prevent black bars
        );
    }

    public function getName(): string
    {
        return 'Mobile';
    }

    public function getType(): string
    {
        return 'platform';
    }
}
