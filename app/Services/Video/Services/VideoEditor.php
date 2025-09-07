<?php

declare(strict_types=1);

namespace App\Services\Video\Services;

use App\Services\Video\Contracts\ScaleStrategyContract;
use App\Services\Video\Contracts\VideoFormatContract;
use App\Services\Video\Enums\BitrateEnum;
use App\Services\Video\Operations\CropOperation;
use App\Services\Video\Operations\ResizeOperation;
use App\Services\Video\Operations\ResizeToHeightOperation;
use App\Services\Video\Operations\ResizeToWidthOperation;
use App\Services\Video\Operations\ScaleOperation;
use App\Services\Video\Operations\TrimOperation;
use App\Services\Video\Operations\WatermarkOperation;
use App\Services\Video\Pipeline\VideoOperationPipeline;
use App\Services\Video\Services\VideoNamingService;
use App\Services\Video\ValueObjects\AspectRatio;
use App\Services\Video\ValueObjects\Dimension;
use App\Services\Video\Video;
use InvalidArgumentException;

final class VideoEditor
{
    private VideoOperationPipeline $pipeline;

    private null|VideoFormatContract $convertFormat = null;

    private null|BitrateEnum $convertBitrate = null;

    public function __construct(
        private readonly Video $video,
    ) {
        $this->pipeline = new VideoOperationPipeline(
            $video->getPath(),
            $video->getDisk(),
        );
    }

    /**
     * Scale video using a scaling strategy.
     */
    public function scale(ScaleStrategyContract $strategy): self
    {
        $this->pipeline->addOperation(
            new ScaleOperation($strategy, $this->video->getDimension()),
        );

        return $this;
    }

    /**
     * Resize video to specific dimensions.
     */
    public function resize(Dimension $dimension): self
    {
        $this->pipeline->addOperation(new ResizeOperation($dimension));

        return $this;
    }

    /**
     * Resize video to specific width (maintaining aspect ratio).
     */
    public function resizeToWidth(int $width): self
    {
        throw_if(
            $width <= 0,
            new InvalidArgumentException('Width must be positive'),
        );

        $this->pipeline->addOperation(new ResizeToWidthOperation($width));

        return $this;
    }

    /**
     * Resize video to specific height (maintaining aspect ratio).
     */
    public function resizeToHeight(int $height): self
    {
        throw_if(
            $height <= 0,
            new InvalidArgumentException('Height must be positive'),
        );

        $this->pipeline->addOperation(new ResizeToHeightOperation($height));

        return $this;
    }

    /**
     * Crop video to specific area.
     */
    public function crop(int $x, int $y, Dimension $dimension): self
    {
        throw_if(
            $x < 0 || $y < 0,
            new InvalidArgumentException(
                'Crop coordinates must be non-negative',
            ),
        );

        $this->pipeline->addOperation(new CropOperation($x, $y, $dimension));

        return $this;
    }

    /**
     * Crop video to specific aspect ratio.
     */
    public function cropToAspectRatio(AspectRatio $aspectRatio): self
    {
        // Note: cropToAspectRatio would need a CropToAspectRatioOperation implementation
        // For now, skip adding to pipeline

        return $this;
    }

    /**
     * Fit video within bounds.
     */
    public function fit(Dimension $bounds, string $position = 'center'): self
    {
        // Note: fit would need a FitOperation implementation
        // For now, skip adding to pipeline

        return $this;
    }

    /**
     * Trim video from start time with duration.
     */
    public function trim(float $start, float $duration): self
    {
        throw_if(
            $start < 0 || $duration <= 0,
            new InvalidArgumentException(
                'Start time and duration must be positive',
            ),
        );

        $this->pipeline->addOperation(new TrimOperation($start, $duration));

        return $this;
    }

    /**
     * Add watermark to video.
     */
    public function watermark(
        string $path,
        string $position = 'bottom-right',
        float $opacity = 1.0,
    ): self {
        throw_if(
            !file_exists($path),
            new InvalidArgumentException("Watermark file not found: {$path}"),
        );
        throw_if(
            $opacity < 0 || $opacity > 1,
            new InvalidArgumentException('Opacity must be between 0 and 1'),
        );

        $this->pipeline->addOperation(new WatermarkOperation(
            $path,
            $position,
            $opacity,
        ));

        return $this;
    }

    /**
     * Add audio to video.
     */
    public function addAudio(string $audioPath): self
    {
        throw_if(
            !file_exists($audioPath),
            new InvalidArgumentException("Audio file not found: {$audioPath}"),
        );

        // Note: addAudio would need an AddAudioOperation implementation
        // For now, skip adding to pipeline

        return $this;
    }

    /**
     * Remove audio from video.
     */
    public function removeAudio(): self
    {
        // Note: removeAudio would need a RemoveAudioOperation implementation
        // For now, skip adding to pipeline

        return $this;
    }

    /**
     * Convert video to specific format with optional bitrate.
     * If no bitrate provided, uses original video bitrate.
     */
    public function convertTo(
        VideoFormatContract $format,
        null|BitrateEnum $bitrate = null,
    ): self {
        $this->convertFormat = $format;
        $this->convertBitrate = $bitrate;

        return $this;
    }

    /**
     * Save video with auto-generated filename using naming service.
     * Always uses CopyFormat unless convertTo() was called.
     */
    public function save(null|VideoNamingService $namingService = null): string
    {
        // Always use naming service - create default if not provided
        $namingService = $namingService ?? VideoNamingService::timestamped();
        $filename = $namingService->generateFilenameFromPattern($this->video);

        $finalPath = $this->executeOperations($filename);

        return $finalPath;
    }

    /**
     * Save video to specific path.
     * Always uses CopyFormat unless convertTo() was called.
     */
    public function saveAs(string $path): string
    {
        $finalPath = $this->executeOperations($path);

        return $finalPath;
    }

    /**
     * Get video object.
     */
    public function getVideo(): Video
    {
        return $this->video;
    }

    /**
     * Get current video dimension.
     */
    public function getDimension(): Dimension
    {
        return $this->video->getDimension();
    }

    /**
     * Get current video aspect ratio.
     */
    public function getAspectRatio(): AspectRatio
    {
        return $this->video->getAspectRatio();
    }

    /**
     * Get video duration in seconds.
     */
    public function getDuration(): float
    {
        return $this->video->getDuration();
    }

    /**
     * Get source path.
     */
    public function getSourcePath(): string
    {
        return $this->video->getPath();
    }

    /**
     * Get the pipeline instance.
     */
    public function getPipeline(): VideoOperationPipeline
    {
        return $this->pipeline;
    }

    /**
     * Execute all operations using the video operation pipeline.
     */
    private function executeOperations(string $outputPath): string
    {
        $directory = dirname($outputPath);
        throw_if(
            !is_dir($directory),
            new InvalidArgumentException(
                "Output directory does not exist: {$directory}",
            ),
        );

        // Pass format and bitrate to pipeline if conversion requested
        if ($this->convertFormat) {
            $this->pipeline->setConvertFormat(
                $this->convertFormat,
                $this->convertBitrate,
            );
        }

        // Execute the pipeline and return final path
        return $this->pipeline->execute($outputPath);
    }
}
