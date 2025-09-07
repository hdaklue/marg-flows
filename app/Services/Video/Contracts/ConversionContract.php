<?php

declare(strict_types=1);

namespace App\Services\Video\Contracts;

use App\Services\Video\ValueObjects\Dimension;
use ProtoneMedia\LaravelFFMpeg\Exporters\MediaExporter;

interface ConversionContract
{
    public function apply(MediaExporter $exporter): void;

    public function getFormat(): string;

    public function getQuality(): string;

    public function getDimension(): null|Dimension;

    public function getTargetBitrate(): null|int;

    public function getName(): string;

    public function getType(): string;

    public function allowScaleUp(): bool;

    public function getMaxDimension(): null|Dimension;

    public function getMinDimension(): null|Dimension;

    public function shouldMaintainAspectRatio(): bool;

    public function getConstraints(): array;

    public function calculateFinalDimension(Dimension $currentDimension): null|Dimension;

    public function wouldScaleUp(Dimension $currentDimension): bool;

    /**
     * Get the filter for this conversion.
     */
    public function getFilter();
}
