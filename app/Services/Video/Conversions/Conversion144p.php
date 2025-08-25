<?php

declare(strict_types=1);

namespace App\Services\Video\Conversions;

use App\Services\Video\ValueObjects\Dimension;
use ProtoneMedia\LaravelFFMpeg\Exporters\MediaExporter;

class Conversion144p extends AbstractConversion
{
    protected string $format = 'mp4';
    protected string $quality = 'low';
    protected ?int $bitrate = 200;
    protected bool $allowScaleUp = true;

    public function __construct(bool $allowScaleUp = true)
    {
        $this->dimension = Dimension::from(256, 144);
        $this->allowScaleUp = $allowScaleUp;
        
        // Constraints for 144p (ultra low quality)
        $this->maxDimension = Dimension::from(256, 144);
        $this->minDimension = Dimension::from(144, 96);
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
        return '144p';
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