<?php

declare(strict_types=1);

namespace App\Services\Video\Resolutions;

use App\Services\Video\ValueObjects\Resolution;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Filters\Video\ResizeFilter;
use ProtoneMedia\LaravelFFMpeg\Exporters\MediaExporter;

final class Resolution720p extends AbstractResolution
{
    protected string $format = 'mp4';

    protected string $quality = 'high';

    protected bool $allowScaleUp = true;

    protected Resolution $resolution;

    public function __construct(string $orientation, bool $allowScaleUp = true)
    {
        $this->resolution = Resolution::create720p($orientation);
        $this->dimension = $this->resolution->dimension;
        $this->bitrate = $this->resolution->getBitrateKbps();
        $this->allowScaleUp = $allowScaleUp;

        // Constraints for 720p HD - adjust based on orientation
        $this->maxDimension = $this->resolution->dimension;
        $this->minDimension = match ($orientation) {
            'portrait' => \App\Services\Video\ValueObjects\Dimension::from(480, 854),
            'square' => \App\Services\Video\ValueObjects\Dimension::from(480, 480),
            default => \App\Services\Video\ValueObjects\Dimension::from(854, 480),
        };
    }

    public static function withoutScaleUp(): self
    {
        return new self('landscape', false);
    }

    /**
     * Create landscape 720p without scale up.
     */
    public static function landscapeWithoutScaleUp(): self
    {
        return new self('landscape', false);
    }

    /**
     * Create portrait 720p without scale up.
     */
    public static function portraitWithoutScaleUp(): self
    {
        return new self('portrait', false);
    }

    /**
     * Create Resolution720p based on source video orientation.
     */
    public static function forOrientation(string $orientation, bool $allowScaleUp = true): self
    {
        return new self($orientation, $allowScaleUp);
    }

    /**
     * Create Resolution720p that matches source video dimension orientation.
     */
    public static function matchingOrientation(
        \App\Services\Video\ValueObjects\Dimension $sourceDimension,
        bool $allowScaleUp = true,
    ): self {
        return new self($sourceDimension->getOrientation(), $allowScaleUp);
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
        return '720p';
    }

    public function getType(): string
    {
        return 'quality';
    }
}
