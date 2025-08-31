<?php

declare(strict_types=1);

namespace App\Services\Video\Operations;

use App\Services\Video\Enums\BitrateEnum;
use FFMpeg\Format\VideoInterface;
use ProtoneMedia\LaravelFFMpeg\Exporters\MediaExporter;
use ProtoneMedia\LaravelFFMpeg\MediaOpener;

final class ConvertOperation extends AbstractVideoOperation
{
    public function __construct(
        private readonly VideoInterface $format,
        private readonly ?BitrateEnum $bitrate = null
    ) {
        $this->metadata = [
            'format' => get_class($this->format),
            'bitrate' => $this->bitrate?->getKbps(),
            'quality_tier' => $this->bitrate?->getQualityTier(),
        ];
    }

    public function execute(MediaExporter $mediaExporter): MediaExporter
    {
        $format = clone $this->format;
        
        // Apply bitrate if provided and format supports it
        if ($this->bitrate && method_exists($format, 'setKiloBitrate')) {
            $format->setKiloBitrate($this->bitrate->getKbps());
        }
        
        return $mediaExporter->inFormat($format);
    }

    public function applyToBuilder(MediaExporter $builder): MediaExporter
    {
        return $this->execute($builder);
    }

    public function applyToMedia(MediaOpener $media): MediaOpener
    {
        // ConvertOperation is applied at export time, not during media processing
        // Return media unchanged - format will be applied in pipeline
        return $media;
    }

    public function getName(): string
    {
        return 'convert';
    }

    public function getFormat(): VideoInterface
    {
        $format = clone $this->format;
        
        if ($this->bitrate && method_exists($format, 'setKiloBitrate')) {
            $format->setKiloBitrate($this->bitrate->getKbps());
        }
        
        return $format;
    }

    public function canExecute(): bool
    {
        return $this->format !== null;
    }
}