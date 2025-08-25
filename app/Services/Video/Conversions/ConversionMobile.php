<?php

declare(strict_types=1);

namespace App\Services\Video\Conversions;

use App\Services\Video\ValueObjects\Dimension;
use ProtoneMedia\LaravelFFMpeg\Exporters\MediaExporter;

class ConversionMobile extends AbstractConversion
{
    protected string $format = 'mp4';
    protected string $quality = 'medium';
    protected ?int $bitrate = 1500;
    protected bool $allowScaleUp = true; // Mobile often needs upscaling from small sources
    protected ?Dimension $maxDimension = null;
    protected ?Dimension $minDimension = null;

    public function __construct(?Dimension $dimension = null, bool $allowScaleUp = true)
    {
        $this->dimension = $dimension ?? Dimension::from(1080, 1920); // Portrait by default
        $this->allowScaleUp = $allowScaleUp;
        
        // Set mobile-friendly constraints
        $this->maxDimension = Dimension::from(1080, 1920);
        $this->minDimension = Dimension::from(480, 640);
    }

    public function apply(MediaExporter $exporter): void
    {
        $format = new \FFMpeg\Format\Video\X264();
        $format->setKiloBitrate($this->bitrate);
        
        $exporter->inFormat($format)
                 ->resize($this->dimension->getWidth(), $this->dimension->getHeight(), 'fit');
    }

    public function getName(): string
    {
        return 'Mobile';
    }

    public function getType(): string
    {
        return 'platform';
    }

    public static function landscape(): self
    {
        return new self(Dimension::from(1920, 1080));
    }

    public static function portrait(): self
    {
        return new self(Dimension::from(1080, 1920));
    }

    public static function square(): self
    {
        return new self(Dimension::from(1080, 1080));
    }
}