<?php

declare(strict_types=1);

namespace App\Services\Video\Services;

use App\Services\Video\Contracts\ConversionContract;
use App\Services\Video\Contracts\ScaleStrategyContract;
use App\Services\Video\Enums\NamingPattern;
use App\Services\Video\Operations\{ConversionOperation, CropOperation, ResizeOperation, ResizeToWidthOperation, ResizeToHeightOperation, ScaleOperation, TrimOperation, WatermarkOperation};
use App\Services\Video\Pipeline\VideoOperationPipeline;
use App\Services\Video\ValueObjects\{Dimension, AspectRatio};
use InvalidArgumentException;

class VideoEditor
{
    private string $sourcePath;
    private bool $isUrl;
    private array $operations = [];
    private string $disk;

    public function __construct(string $sourcePath, bool $isUrl = false, string $disk = 'local')
    {
        $this->sourcePath = $sourcePath;
        $this->isUrl = $isUrl;
        $this->disk = $disk;
    }

    /**
     * Scale video using a scaling strategy.
     */
    public function scale(ScaleStrategyContract $strategy): self
    {
        $this->operations[] = [
            'type' => 'scale',
            'strategy' => $strategy,
        ];

        return $this;
    }

    /**
     * Resize video to specific dimensions.
     */
    public function resize(Dimension $dimension): self
    {
        $this->operations[] = [
            'type' => 'resize',
            'dimension' => $dimension,
        ];

        return $this;
    }

    /**
     * Resize video to specific width (maintaining aspect ratio).
     */
    public function resizeToWidth(int $width): self
    {
        throw_if($width <= 0, new InvalidArgumentException('Width must be positive'));

        $this->operations[] = [
            'type' => 'resize_width',
            'width' => $width,
        ];

        return $this;
    }

    /**
     * Resize video to specific height (maintaining aspect ratio).
     */
    public function resizeToHeight(int $height): self
    {
        throw_if($height <= 0, new InvalidArgumentException('Height must be positive'));

        $this->operations[] = [
            'type' => 'resize_height',
            'height' => $height,
        ];

        return $this;
    }

    /**
     * Crop video to specific area.
     */
    public function crop(int $x, int $y, Dimension $dimension): self
    {
        throw_if($x < 0 || $y < 0, new InvalidArgumentException('Crop coordinates must be non-negative'));

        $this->operations[] = [
            'type' => 'crop',
            'x' => $x,
            'y' => $y,
            'dimension' => $dimension,
        ];

        return $this;
    }

    /**
     * Crop video to specific aspect ratio.
     */
    public function cropToAspectRatio(AspectRatio $aspectRatio): self
    {
        $this->operations[] = [
            'type' => 'crop_aspect_ratio',
            'aspect_ratio' => $aspectRatio,
        ];

        return $this;
    }

    /**
     * Fit video within bounds.
     */
    public function fit(Dimension $bounds, string $position = 'center'): self
    {
        $this->operations[] = [
            'type' => 'fit',
            'bounds' => $bounds,
            'position' => $position,
        ];

        return $this;
    }

    /**
     * Trim video from start time with duration.
     */
    public function trim(float $start, float $duration): self
    {
        throw_if($start < 0 || $duration <= 0, new InvalidArgumentException('Start time and duration must be positive'));

        $this->operations[] = [
            'type' => 'trim',
            'start' => $start,
            'duration' => $duration,
        ];

        return $this;
    }

    /**
     * Add watermark to video.
     */
    public function watermark(string $path, string $position = 'bottom-right', float $opacity = 1.0): self
    {
        throw_if(!file_exists($path), new InvalidArgumentException("Watermark file not found: {$path}"));
        throw_if($opacity < 0 || $opacity > 1, new InvalidArgumentException('Opacity must be between 0 and 1'));

        $this->operations[] = [
            'type' => 'watermark',
            'path' => $path,
            'position' => $position,
            'opacity' => $opacity,
        ];

        return $this;
    }

    /**
     * Add audio to video.
     */
    public function addAudio(string $audioPath): self
    {
        throw_if(!file_exists($audioPath), new InvalidArgumentException("Audio file not found: {$audioPath}"));

        $this->operations[] = [
            'type' => 'add_audio',
            'path' => $audioPath,
        ];

        return $this;
    }

    /**
     * Remove audio from video.
     */
    public function removeAudio(): self
    {
        $this->operations[] = [
            'type' => 'remove_audio',
        ];

        return $this;
    }


    /**
     * Apply conversion to video.
     */
    public function convert(ConversionContract $conversion): self
    {
        $this->operations[] = [
            'type' => 'convert',
            'conversion' => $conversion,
        ];

        return $this;
    }

    /**
     * Save video to path.
     */
    public function save(string $path): void
    {
        $this->executeOperations($path);
    }

    /**
     * Save video with specific conversion.
     */
    public function saveAs(string $path, ConversionContract $conversion): void
    {
        $this->convert($conversion)->save($path);
    }

    /**
     * Save video with auto-generated filename using naming service.
     */
    public function saveWithNaming(string $directory, ConversionContract $conversion, ?NamingPattern $pattern = null): string
    {
        $namingService = new VideoNamingService($pattern);
        $filename = $namingService->generateName($this->sourcePath, $conversion);
        $fullPath = rtrim($directory, '/') . '/' . $filename;
        
        $this->saveAs($fullPath, $conversion);
        
        return $fullPath;
    }

    /**
     * Get current video dimension (requires analysis).
     */
    public function getDimension(): Dimension
    {
        // This would analyze the video using FFMpeg
        // For now, return a placeholder
        return Dimension::from(1920, 1080);
    }

    /**
     * Get current video aspect ratio.
     */
    public function getAspectRatio(): AspectRatio
    {
        $dimension = $this->getDimension();
        return $dimension->getAspectRatio();
    }

    /**
     * Get video duration in seconds.
     */
    public function getDuration(): float
    {
        // This would analyze the video using FFMpeg
        // For now, return a placeholder
        return 0.0;
    }

    /**
     * Get source path.
     */
    public function getSourcePath(): string
    {
        return $this->sourcePath;
    }

    /**
     * Get all queued operations.
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    /**
     * Execute all operations using the video operation pipeline.
     */
    private function executeOperations(string $outputPath): void
    {
        $directory = dirname($outputPath);
        throw_if(!is_dir($directory), new InvalidArgumentException("Output directory does not exist: {$directory}"));

        // Create pipeline
        $pipeline = new VideoOperationPipeline($this->sourcePath, $this->disk);
        
        // Convert array operations to operation objects and add to pipeline
        foreach ($this->operations as $operation) {
            $operationObject = $this->createOperationObject($operation);
            
            if ($operationObject) {
                $pipeline->addOperation($operationObject);
            }
        }

        // Execute the pipeline
        $pipeline->execute($outputPath);
        
        // Store execution log for debugging (optional)
        // Log::debug('Video pipeline executed', $pipeline->getExecutionLog());
    }

    /**
     * Create operation object from array data.
     */
    private function createOperationObject(array $operation): ?object
    {
        return match($operation['type']) {
            'scale' => new ScaleOperation(
                $operation['strategy'], 
                $this->getDimension()
            ),
            'resize' => new ResizeOperation($operation['dimension']),
            'resize_width' => new ResizeToWidthOperation($operation['width']),
            'resize_height' => new ResizeToHeightOperation($operation['height']),
            'trim' => new TrimOperation(
                $operation['start'], 
                $operation['duration']
            ),
            'crop' => new CropOperation(
                $operation['x'],
                $operation['y'],
                $operation['dimension']
            ),
            'watermark' => new WatermarkOperation(
                $operation['path'],
                $operation['position'] ?? 'bottom-right',
                $operation['opacity'] ?? 1.0
            ),
            'convert' => new ConversionOperation($operation['conversion']),
            default => null
        };
    }
}