<?php

declare(strict_types=1);

namespace App\Services\Video\Conversions;

use App\Services\Video\ValueObjects\Dimension;
use ProtoneMedia\LaravelFFMpeg\Exporters\MediaExporter;

class Conversion4K extends AbstractConversion
{
    protected string $format = 'mp4';
    protected string $quality = 'high';
    protected ?int $bitrate = 8000;
    protected bool $allowScaleUp = false; // Don't scale up lower res videos
    protected ?Dimension $maxDimension = null;
    protected ?Dimension $minDimension = null;

    public function __construct(bool $allowScaleUp = false)
    {
        $this->dimension = Dimension::from(3840, 2160);
        $this->allowScaleUp = $allowScaleUp;
        
        // Set reasonable constraints for 4K
        $this->maxDimension = Dimension::from(4096, 2160); // DCI 4K max
        $this->minDimension = Dimension::from(1920, 1080); // Min Full HD for upscaling
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
        return '4K';
    }

    public function getType(): string
    {
        return 'quality';
    }

    public static function allowingScaleUp(): self
    {
        return new self(true);
    }
}