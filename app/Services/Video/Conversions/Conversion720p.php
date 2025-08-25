<?php

declare(strict_types=1);

namespace App\Services\Video\Conversions;

use App\Services\Video\ValueObjects\Dimension;
use ProtoneMedia\LaravelFFMpeg\Exporters\MediaExporter;

class Conversion720p extends AbstractConversion
{
    protected string $format = 'mp4';
    protected string $quality = 'high';
    protected ?int $bitrate = 2500;
    protected bool $allowScaleUp = true;

    public function __construct(bool $allowScaleUp = true)
    {
        $this->dimension = Dimension::from(1280, 720);
        $this->allowScaleUp = $allowScaleUp;
        
        // Constraints for 720p HD
        $this->maxDimension = Dimension::from(1280, 720);
        $this->minDimension = Dimension::from(854, 480);
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
        return '720p';
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