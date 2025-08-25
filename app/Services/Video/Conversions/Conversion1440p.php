<?php

declare(strict_types=1);

namespace App\Services\Video\Conversions;

use App\Services\Video\ValueObjects\Dimension;
use ProtoneMedia\LaravelFFMpeg\Exporters\MediaExporter;

class Conversion1440p extends AbstractConversion
{
    protected string $format = 'mp4';
    protected string $quality = 'high';
    protected ?int $bitrate = 6000;
    protected bool $allowScaleUp = false; // QHD usually shouldn't scale up

    public function __construct(bool $allowScaleUp = false)
    {
        $this->dimension = Dimension::from(2560, 1440);
        $this->allowScaleUp = $allowScaleUp;
        
        // Constraints for 1440p QHD
        $this->maxDimension = Dimension::from(2560, 1440);
        $this->minDimension = Dimension::from(1280, 720);
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
        return '1440p';
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