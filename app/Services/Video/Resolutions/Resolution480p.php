<?php

declare(strict_types=1);

namespace App\Services\Video\Resolutions;

use App\Services\Video\ValueObjects\Resolution;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Filters\Video\ResizeFilter;
use ProtoneMedia\LaravelFFMpeg\Exporters\MediaExporter;

final class Resolution480p extends AbstractResolution
{
    protected string $format = 'mp4';

    protected string $quality = 'medium';

    protected bool $allowScaleUp = true;

    protected Resolution $resolution;

    public function __construct(string $orientation, bool $allowScaleUp = true)
    {
        $this->resolution = Resolution::create480p($orientation);
        $this->dimension = $this->resolution->dimension;
        $this->bitrate = $this->resolution->getBitrateKbps();
        $this->allowScaleUp = $allowScaleUp;

        // Constraints for 480p - adjust based on orientation
        $this->maxDimension = $this->resolution->dimension;
        $this->minDimension = match($orientation) {
            'portrait' => \App\Services\Video\ValueObjects\Dimension::from(360, 640),
            'square' => \App\Services\Video\ValueObjects\Dimension::from(360, 360),
            default => \App\Services\Video\ValueObjects\Dimension::from(640, 360),
        };
    }

    /**
     * Create landscape 480p without scale up.
     */
    public static function landscapeWithoutScaleUp(): self
    {
        return new self('landscape', false);
    }
    
    /**
     * Create portrait 480p without scale up.
     */
    public static function portraitWithoutScaleUp(): self
    {
        return new self('portrait', false);
    }
    
    /**
     * Create Resolution480p based on source video orientation.
     */
    public static function forOrientation(string $orientation, bool $allowScaleUp = true): self
    {
        return new self($orientation, $allowScaleUp);
    }
    
    /**
     * Create Resolution480p that matches source video dimension orientation.
     */
    public static function matchingOrientation(\App\Services\Video\ValueObjects\Dimension $sourceDimension, bool $allowScaleUp = true): self
    {
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
            false  // Disable standard aspect ratio enforcement to prevent black bars
        );
    }

    public function getName(): string
    {
        return '480p';
    }

    public function getType(): string
    {
        return 'quality';
    }
}
