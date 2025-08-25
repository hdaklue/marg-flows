<?php

declare(strict_types=1);

namespace App\Services\Video\Pipeline;

use App\Services\Video\Contracts\VideoOperationContract;
use Illuminate\Pipeline\Pipeline;
use InvalidArgumentException;
use ProtoneMedia\LaravelFFMpeg\Exporters\MediaExporter;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use ProtoneMedia\LaravelFFMpeg\FFMpeg\CopyFormat;

class VideoOperationPipeline
{
    /**
     * @var VideoOperationContract[]
     */
    private array $operations = [];
    
    private array $executionLog = [];

    public function __construct(
        private readonly string $sourcePath,
        private readonly string $disk = 'local'
    ) {}

    /**
     * Add an operation to the pipeline.
     */
    public function addOperation(VideoOperationContract $operation): self
    {
        // Set execution index based on current position
        $operation->setExecutionIndex(count($this->operations));
        
        $this->operations[] = $operation;
        
        return $this;
    }

    /**
     * Add multiple operations to the pipeline.
     */
    public function addOperations(array $operations): self
    {
        foreach ($operations as $operation) {
            if (!$operation instanceof VideoOperationContract) {
                throw new InvalidArgumentException('All operations must implement VideoOperationContract');
            }
            
            $this->addOperation($operation);
        }

        return $this;
    }

    /**
     * Execute all operations using Laravel's Pipeline.
     */
    public function execute(string $outputPath): void
    {
        $this->executionLog = [];
        
        // Create initial media object (not exporter yet)
        try {
            $media = FFMpeg::fromDisk($this->disk)->open($this->sourcePath);
        } catch (\Exception $e) {
            throw new InvalidArgumentException("Could not open source video: {$this->sourcePath}. Error: " . $e->getMessage());
        }
        
        // Apply all filters to the media object first
        $mediaExporter = $media->export();
        
        // Execute operations through Laravel Pipeline
        app(Pipeline::class)
            ->send($mediaExporter)
            ->through($this->operations)
            ->then(function ($mediaExporter) use ($outputPath) {
                // Set format based on output file extension before save
                $mediaExporter = $this->setFormatFromExtension($mediaExporter, $outputPath);
                
                // Save the final result
                $mediaExporter->save($outputPath);
                $this->logPipelineCompletion($outputPath);
                
                return $mediaExporter;
            });
    }

    /**
     * Get the execution log.
     */
    public function getExecutionLog(): array
    {
        return $this->executionLog;
    }

    /**
     * Get operations count.
     */
    public function getOperationsCount(): int
    {
        return count($this->operations);
    }

    /**
     * Get operations metadata.
     */
    public function getOperationsMetadata(): array
    {
        return array_map(fn($op) => $op->getMetadata(), $this->operations);
    }

    /**
     * Log successful operation execution.
     */
    private function logSuccessfulOperation(VideoOperationContract $operation, float $startTime): void
    {
        $this->executionLog[] = [
            'operation' => $operation->getName(),
            'status' => 'success',
            'execution_time' => round((microtime(true) - $startTime) * 1000, 2) . 'ms',
            'metadata' => $operation->getMetadata(),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Log failed operation execution.
     */
    private function logFailedOperation(VideoOperationContract $operation, \Exception $e, float $startTime): void
    {
        $this->executionLog[] = [
            'operation' => $operation->getName(),
            'status' => 'failed',
            'error' => $e->getMessage(),
            'execution_time' => round((microtime(true) - $startTime) * 1000, 2) . 'ms',
            'metadata' => $operation->getMetadata(),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Log skipped operation.
     */
    private function logSkippedOperation(VideoOperationContract $operation, string $reason): void
    {
        $this->executionLog[] = [
            'operation' => $operation->getName(),
            'status' => 'skipped',
            'reason' => $reason,
            'metadata' => $operation->getMetadata(),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Log pipeline completion.
     */
    private function logPipelineCompletion(string $outputPath): void
    {
        $this->executionLog[] = [
            'pipeline' => 'completed',
            'output_path' => $outputPath,
            'total_operations' => $this->getOperationsCount(),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Set format based on file extension.
     * Uses CopyFormat only for operations that don't require transcoding.
     */
    private function setFormatFromExtension(MediaExporter $mediaExporter, string $outputPath): MediaExporter
    {
        $extension = strtolower(pathinfo($outputPath, PATHINFO_EXTENSION));
        $sourceExtension = strtolower(pathinfo($this->sourcePath, PATHINFO_EXTENSION));
        
        // If same extension and ONLY simple operations that don't require transcoding, use CopyFormat
        if ($extension === $sourceExtension && !$this->hasConversionOperations() && !$this->hasFilterOperations()) {
            return $mediaExporter->inFormat(new CopyFormat());
        }
        
        // Otherwise, use appropriate format for transcoding
        return match ($extension) {
            'mp4', 'mov' => $mediaExporter->inFormat(new \FFMpeg\Format\Video\X264()),
            'avi', 'wmv' => $mediaExporter->inFormat(new \FFMpeg\Format\Video\WMV()),
            'webm' => $mediaExporter->inFormat(new \FFMpeg\Format\Video\WebM()),
            'ogg' => $mediaExporter->inFormat(new \FFMpeg\Format\Video\Ogg()),
            default => $mediaExporter->inFormat(new \FFMpeg\Format\Video\X264()), // Default to X264
        };
    }

    /**
     * Check if pipeline has conversion operations that require transcoding.
     */
    private function hasConversionOperations(): bool
    {
        foreach ($this->operations as $operation) {
            if ($operation->getName() === 'conversion') {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if pipeline has filter operations that require transcoding.
     * Operations like resize, scale, crop, watermark require transcoding.
     */
    private function hasFilterOperations(): bool
    {
        $filterOperations = ['resize', 'resize_to_width', 'resize_to_height', 'scale', 'crop', 'watermark'];
        
        foreach ($this->operations as $operation) {
            if (in_array($operation->getName(), $filterOperations, true)) {
                return true;
            }
        }
        return false;
    }
}