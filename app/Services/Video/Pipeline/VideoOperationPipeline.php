<?php

declare(strict_types=1);

namespace App\Services\Video\Pipeline;

use App\Services\Video\Contracts\VideoFormatContract;
use App\Services\Video\Contracts\VideoOperationContract;
use App\Services\Video\Enums\BitrateEnum;
use App\Services\Video\Services\VideoPipelineExporter;
use Exception;
use InvalidArgumentException;

final class VideoOperationPipeline
{
    /**
     * @var VideoOperationContract[]
     */
    private array $operations = [];

    private array $executionLog = [];

    private bool $forceTranscoding = false;

    private ?VideoFormatContract $convertFormat = null;

    private ?BitrateEnum $convertBitrate = null;

    private ?int $forcedBitrate = null;

    public function __construct(
        private readonly string $sourcePath,
        private readonly string $disk = 'local',
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
            if (! $operation instanceof VideoOperationContract) {
                throw new InvalidArgumentException(
                    'All operations must implement VideoOperationContract',
                );
            }

            $this->addOperation($operation);
        }

        return $this;
    }

    /**
     * Set conversion format and bitrate for the pipeline.
     */
    public function setConvertFormat(
        VideoFormatContract $format,
        ?BitrateEnum $bitrate = null,
    ): self {
        $this->convertFormat = $format;
        $this->convertBitrate = $bitrate;

        return $this;
    }

    /**
     * Execute all operations using Laravel FFMpeg builder pattern.
     * Returns the final output path (may be different if format changes extension).
     */
    public function execute(string $outputPath): string
    {
        $this->executionLog = [];

        $exporter = new VideoPipelineExporter(
            $this,
            $this->sourcePath,
            $this->disk,
        );
        $finalPath = $exporter->export(
            $outputPath,
            $this->convertFormat,
            $this->convertBitrate,
        );

        $this->logPipelineCompletion($finalPath);

        return $finalPath;
    }

    /**
     * Get the execution log.
     */
    public function getExecutionLog(): array
    {
        return $this->executionLog;
    }

    /**
     * Get all operations.
     */
    public function getOperations(): array
    {
        return $this->operations;
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
        return array_map(fn ($op) => $op->getMetadata(), $this->operations);
    }

    /**
     * Force transcoding even if operations might not require it.
     */
    public function forceTranscoding(?int $bitrate = null): self
    {
        $this->forceTranscoding = true;
        if ($bitrate) {
            $this->forcedBitrate = $bitrate;
        }

        return $this;
    }

    /**
     * Log successful operation execution.
     */
    private function logSuccessfulOperation(
        VideoOperationContract $operation,
        float $startTime,
    ): void {
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
    private function logFailedOperation(
        VideoOperationContract $operation,
        Exception $e,
        float $startTime,
    ): void {
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
    private function logSkippedOperation(
        VideoOperationContract $operation,
        string $reason,
    ): void {
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
}
