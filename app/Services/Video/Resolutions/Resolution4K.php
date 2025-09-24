<?php

declare(strict_types=1);

namespace App\Services\Video\Resolutions;

use App\Services\Video\ValueObjects\Resolution;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Filters\Video\ResizeFilter;
use ProtoneMedia\LaravelFFMpeg\Exporters\MediaExporter;

final class Resolution4K extends AbstractResolution
{
    protected string $format = 'mp4';

    protected string $quality = 'high';

    protected bool $allowScaleUp = false; // Don't scale up lower res videos

    protected Resolution $resolution;

    public function __construct(string $orientation, bool $allowScaleUp = false)
    {
        $this->resolution = Resolution::create4K($orientation);
        $this->dimension = $this->resolution->dimension;
        $this->bitrate = $this->resolution->getBitrateKbps();
        $this->allowScaleUp = $allowScaleUp;

        // Set reasonable constraints for 4K - adjust based on orientation
        $this->maxDimension = match ($orientation) {
            'portrait' => \App\Services\Video\ValueObjects\Dimension::from(2160, 4096), // Portrait 4K max
            'square' => \App\Services\Video\ValueObjects\Dimension::from(2160, 2160), // Square 4K max
            default => \App\Services\Video\ValueObjects\Dimension::from(4096, 2160), // Landscape 4K max (DCI)
        };
        $this->minDimension = match ($orientation) {
            'portrait' => \App\Services\Video\ValueObjects\Dimension::from(1080, 1920), // Portrait Full HD min
            'square' => \App\Services\Video\ValueObjects\Dimension::from(1080, 1080), // Square Full HD min
            default => \App\Services\Video\ValueObjects\Dimension::from(1920, 1080), // Landscape Full HD min
        };
    }

    public static function allowingScaleUp(): self
    {
        return new self('landscape', true);
    }

    /**
     * Create Resolution4K based on source video orientation.
     */
    public static function forOrientation(string $orientation, bool $allowScaleUp = false): self
    {
        return new self($orientation, $allowScaleUp);
    }

    public function apply(MediaExporter $exporter): void
    {
        // Legacy method - not used in new pattern
        $filter = $this->getFilter();
        $exporter->addFilter($filter);
    }

    public function getFilter()
    {
        // Create ResizeFilter directly instead of using closure
        return new ResizeFilter(
            new Dimension($this->dimension->getWidth(), $this->dimension->getHeight()),
            $this->resizeMode,
            false, // Disable standard aspect ratio enforcement to prevent black bars
        );
    }

    public function getName(): string
    {
        return '4K';
    }

    public function getType(): string
    {
        return 'quality';
    }
}
