<?php

declare(strict_types=1);

namespace App\Services\Video\Conversions;

use App\Services\Video\ValueObjects\Dimension;
use ProtoneMedia\LaravelFFMpeg\Exporters\MediaExporter;

class Conversion1080p extends AbstractConversion
{
    protected string $format = 'mp4';
    protected string $quality = 'high';
    protected ?int $bitrate = 5000;
    protected bool $allowScaleUp = true; // Common to scale up to 1080p
    protected ?Dimension $maxDimension = null;
    protected ?Dimension $minDimension = null;

    public function __construct(bool $allowScaleUp = true)
    {
        $this->dimension = Dimension::from(1920, 1080);
        $this->allowScaleUp = $allowScaleUp;
        
        // Set reasonable constraints for 1080p
        $this->maxDimension = Dimension::from(1920, 1080);
        $this->minDimension = Dimension::from(640, 360); // Min for upscaling
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
        return '1080p';
    }

    public function getType(): string
    {
        return 'quality';
    }

    public static function withoutScaleUp(): self
    {
        return new self(false);
    }
}