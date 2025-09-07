<?php

declare(strict_types=1);

namespace App\Services\Video\Operations;

use App\Services\Video\Contracts\ConversionContract;
use FFMpeg\Format\Video\X264;
use ProtoneMedia\LaravelFFMpeg\Exporters\MediaExporter;
use ProtoneMedia\LaravelFFMpeg\MediaOpener;

final class ResolutionOperation extends AbstractVideoOperation
{
    public function __construct(
        private readonly ConversionContract $resolution,
    ) {
        $this->metadata = [
            'resolution' => get_class($this->resolution),
            'format' => $this->resolution->getFormat(),
            'quality' => $this->resolution->getQuality(),
            'bitrate' => $this->resolution->getTargetBitrate(),
            'constraints' => $this->resolution->getConstraints(),
        ];
    }

    public function execute(MediaExporter $mediaExporter): MediaExporter
    {
        // Legacy method for backward compatibility
        return $this->applyToBuilder($mediaExporter);
    }

    public function applyToBuilder(MediaExporter $builder): MediaExporter
    {
        // Legacy method - not used in new pattern
        $filter = $this->resolution->getFilter();

        return $builder->addFilter($filter);
    }

    public function applyToMedia(MediaOpener $media): MediaOpener
    {
        // Add filter to media instance BEFORE export
        $filter = $this->resolution->getFilter();

        return $media->addFilter($filter);
    }

    /**
     * Get the format and bitrate for this resolution operation.
     */
    public function getFormat(): X264
    {
        $format = new X264();
        $format->setKiloBitrate($this->resolution->getTargetBitrate());

        return $format;
    }

    public function getName(): string
    {
        return 'resolution';
    }
}
