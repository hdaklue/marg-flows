<?php

declare(strict_types=1);

namespace App\Services\Video\Operations;

use App\Services\Video\Contracts\ConversionContract;
use ProtoneMedia\LaravelFFMpeg\Exporters\MediaExporter;

class ConversionOperation extends AbstractVideoOperation
{

    public function __construct(
        private readonly ConversionContract $conversion
    ) {
        $this->metadata = [
            'conversion' => get_class($this->conversion),
            'format' => $this->conversion->getFormat(),
            'quality' => $this->conversion->getQuality(),
            'bitrate' => $this->conversion->getTargetBitrate(),
            'constraints' => $this->conversion->getConstraints(),
        ];
    }

    public function execute(MediaExporter $mediaExporter): MediaExporter
    {
        $this->conversion->apply($mediaExporter);
        
        return $mediaExporter;
    }

    public function getName(): string
    {
        return 'conversion';
    }
}