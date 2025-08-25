<?php

declare(strict_types=1);

namespace App\Services\Video\Operations;

use App\Services\Video\Contracts\ScaleStrategyContract;
use App\Services\Video\ValueObjects\Dimension;
use ProtoneMedia\LaravelFFMpeg\Exporters\MediaExporter;

class ScaleOperation extends AbstractVideoOperation
{

    public function __construct(
        private readonly ScaleStrategyContract $strategy,
        private readonly Dimension $currentDimension
    ) {
        $this->metadata = [
            'strategy' => get_class($this->strategy),
            'strategy_description' => $this->strategy->getDescription(),
            'current_dimension' => $this->currentDimension->toArray(),
        ];
    }

    public function execute(MediaExporter $mediaExporter): MediaExporter
    {
        $finalDimension = $this->strategy->apply($this->currentDimension, $this->currentDimension);
        
        $this->metadata['final_dimension'] = $finalDimension->toArray();
        
        return $mediaExporter->addFilter(function ($filters) use ($finalDimension) {
            $filters->resize(
                new \FFMpeg\Coordinate\Dimension(
                    $finalDimension->getWidth(),
                    $finalDimension->getHeight()
                ),
                'fit'
            );
        });
    }

    public function getName(): string
    {
        return 'scale';
    }
}