<?php

declare(strict_types=1);

namespace App\Services\Video\Resolutions;

use App\Services\Video\ValueObjects\Resolution;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Filters\Video\ResizeFilter;
use ProtoneMedia\LaravelFFMpeg\Exporters\MediaExporter;

final class Resolution1440p extends AbstractResolution
{
    protected string $format = 'mp4';

    protected string $quality = 'high';

    protected bool $allowScaleUp = false; // QHD usually shouldn't scale up

    protected Resolution $resolution;

    public function __construct(string $orientation, bool $allowScaleUp = false)
    {
        $this->resolution = Resolution::create1440p($orientation);
        $this->dimension = $this->resolution->dimension;
        $this->bitrate = $this->resolution->getBitrateKbps();
        $this->allowScaleUp = $allowScaleUp;

        // Constraints for 1440p QHD - adjust based on orientation
        $this->maxDimension = $this->resolution->dimension;
        $this->minDimension = match ($orientation) {
            'portrait' => \App\Services\Video\ValueObjects\Dimension::from(
                720,
                1280,
            ),
            'square' => \App\Services\Video\ValueObjects\Dimension::from(
                720,
                720,
            ),
            default => \App\Services\Video\ValueObjects\Dimension::from(
                1280,
                720,
            ),
        };
    }

    public static function allowingScaleUp(): self
    {
        return new self('landscape', true);
    }

    /**
     * Create Resolution1440p based on source video orientation.
     */
    public static function forOrientation(
        string $orientation,
        bool $allowScaleUp = false,
    ): self {
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
            new Dimension(
                $this->dimension->getWidth(),
                $this->dimension->getHeight(),
            ),
            $this->resizeMode,
            false, // Disable standard aspect ratio enforcement to prevent black bars
        );
    }

    public function getName(): string
    {
        return '1440p';
    }

    public function getType(): string
    {
        return 'quality';
    }
}
