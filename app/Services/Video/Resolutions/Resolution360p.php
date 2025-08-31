<?php

declare(strict_types=1);

namespace App\Services\Video\Resolutions;

use App\Services\Video\ValueObjects\Resolution;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Filters\Video\ResizeFilter;
use ProtoneMedia\LaravelFFMpeg\Exporters\MediaExporter;

final class Resolution360p extends AbstractResolution
{
    protected string $format = 'mp4';

    protected string $quality = 'low';

    protected Resolution $resolution;

    protected bool $allowScaleUp = true;

    public function __construct(string $orientation, bool $allowScaleUp = true)
    {
        $this->resolution = Resolution::create360p($orientation);
        $this->dimension = $this->resolution->dimension;
        $this->bitrate = $this->resolution->getBitrateKbps();
        $this->allowScaleUp = $allowScaleUp;

        // Constraints for 360p - adjust based on orientation
        $this->maxDimension = $this->resolution->dimension;
        $this->minDimension = match($orientation) {
            'portrait' => \App\Services\Video\ValueObjects\Dimension::from(240, 426),
            'square' => \App\Services\Video\ValueObjects\Dimension::from(240, 240),
            default => \App\Services\Video\ValueObjects\Dimension::from(426, 240),
        };
    }

    public static function withoutScaleUp(): self
    {
        return new self('landscape', false);
    }

    /**
     * Create Resolution360p based on source video orientation.
     */
    public static function forOrientation(string $orientation, bool $allowScaleUp = true): self
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
            false  // Disable standard aspect ratio enforcement to prevent black bars
        );
    }

    public function getName(): string
    {
        return '360p';
    }

    public function getType(): string
    {
        return 'quality';
    }
}
