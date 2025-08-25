<?php

declare(strict_types=1);

namespace App\Services\Video\Conversions;

use App\Services\Video\ValueObjects\Dimension;
use ProtoneMedia\LaravelFFMpeg\Exporters\MediaExporter;

class Conversion240p extends AbstractConversion
{
    protected string $format = 'mp4';
    protected string $quality = 'low';
    protected ?int $bitrate = 400;
    protected bool $allowScaleUp = true;

    public function __construct(bool $allowScaleUp = true)
    {
        $this->dimension = Dimension::from(426, 240);
        $this->allowScaleUp = $allowScaleUp;
        
        // Constraints for 240p
        $this->maxDimension = Dimension::from(426, 240);
        $this->minDimension = Dimension::from(256, 144);
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
        return '240p';
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