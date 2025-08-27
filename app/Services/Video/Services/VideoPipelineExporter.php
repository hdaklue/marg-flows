<?php

declare(strict_types=1);

namespace App\Services\Video\Services;

use App\Services\Video\Contracts\VideoFormatContract;
use App\Services\Video\Enums\BitrateEnum;
use App\Services\Video\Pipeline\VideoOperationPipeline;
use Exception;
use FFMpeg\Format\Video\WebM;
use FFMpeg\Format\Video\WMV;
use FFMpeg\Format\Video\X264;
use InvalidArgumentException;
use ProtoneMedia\LaravelFFMpeg\FFMpeg\CopyFormat;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

final class VideoPipelineExporter
{
    private const array TRANSCODING_OPERATIONS = [
        'trim',
        'resize',
        'resize_to_width',
        'resize_to_height',
        'scale',
        'crop',
        'watermark',
    ];

    public function __construct(
        private readonly VideoOperationPipeline $pipeline,
        private readonly string $sourcePath,
        private readonly string $disk = 'local',
    ) {}

    /**
     * Execute the pipeline and export to the specified output path.
     * Returns the final output path (may be different if format changes extension).
     */
    public function export(string $outputPath, ?VideoFormatContract $convertFormat = null, ?BitrateEnum $convertBitrate = null): string
    {
        $media = $this->openSourceMedia();
        $media = $this->applyOperations($media);
        $exporter = $media->export();

        $outputPath = $this->applyFormat($exporter, $outputPath, $convertFormat, $convertBitrate);
        $exporter->save($outputPath);

        return $outputPath;
    }

    /**
     * Open source media using FFMpeg.
     */
    private function openSourceMedia()
    {
        try {
            return FFMpeg::fromDisk($this->disk)->open($this->sourcePath);
        } catch (Exception $e) {
            throw new InvalidArgumentException("Could not open source video: {$this->sourcePath}. Error: " . $e->getMessage());
        }
    }

    /**
     * Apply all pipeline operations to the media.
     */
    private function applyOperations($media)
    {
        foreach ($this->pipeline->getOperations() as $operation) {
            $media = $operation->applyToMedia($media);
        }

        return $media;
    }

    /**
     * Apply format to the exporter and update output path if needed.
     */
    private function applyFormat($exporter, string $outputPath, ?VideoFormatContract $convertFormat, ?BitrateEnum $convertBitrate): string
    {
        if ($convertFormat) {
            return $this->applyConvertFormat($exporter, $outputPath, $convertFormat, $convertBitrate);
        }

        if ($this->hasOperationsRequiringTranscoding()) {
            $this->applyTranscodingFormat($exporter, $outputPath);
        } else {
            $exporter->inFormat(new CopyFormat);
        }

        return $outputPath;
    }

    /**
     * Apply convert format to exporter.
     */
    private function applyConvertFormat($exporter, string $outputPath, VideoFormatContract $convertFormat, ?BitrateEnum $convertBitrate): string
    {

        $bitrate = $convertBitrate ?: null;
        if (! $bitrate) {
            $this->getOriginalBitrate(); // Get original bitrate but don't convert to enum
        }

        $format = $convertFormat->getDriverFormat($bitrate);
        $extension = $convertFormat->getExtension();
        $outputPath = $this->updateOutputPathExtension($outputPath, $extension);

        $exporter->inFormat($format);

        return $outputPath;
    }

    /**
     * Apply transcoding format based on output file extension.
     */
    private function applyTranscodingFormat($exporter, string $outputPath): void
    {
        $extension = strtolower(pathinfo($outputPath, PATHINFO_EXTENSION));
        $format = match ($extension) {
            'mp4', 'mov' => new X264,
            'webm' => new WebM,
            'avi' => new WMV,
            default => new X264,
        };

        $originalBitrate = $this->getOriginalBitrate();
        if ($originalBitrate && method_exists($format, 'setKiloBitrate')) {
            $format->setKiloBitrate($originalBitrate);
        }

        $exporter->inFormat($format);
    }

    /**
     * Check if pipeline has operations that require transcoding (not CopyFormat).
     */
    private function hasOperationsRequiringTranscoding(): bool
    {
        foreach ($this->pipeline->getOperations() as $operation) {
            if (in_array($operation->getName(), self::TRANSCODING_OPERATIONS, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Update output path extension to match the format.
     */
    private function updateOutputPathExtension(string $outputPath, string $newExtension): string
    {
        $pathInfo = pathinfo($outputPath);
        $directory = $pathInfo['dirname'] ?? '';
        $filename = $pathInfo['filename'] ?? '';

        if ($directory && $directory !== '.') {
            return $directory . DIRECTORY_SEPARATOR . $filename . '.' . $newExtension;
        }

        return $filename . '.' . $newExtension;
    }

    /**
     * Get original video bitrate to preserve quality.
     */
    private function getOriginalBitrate(): ?int
    {
        try {
            $media = FFMpeg::fromDisk($this->disk)->open($this->sourcePath);
            $format = $media->getFormat();

            // Try to get bitrate from format
            if (method_exists($format, 'get') && $format->get('bit_rate')) {
                return (int) ($format->get('bit_rate') / 1000); // Convert to kbps
            }

            return null;
        } catch (Exception $e) {
            // If we can't get the original bitrate, return null
            return null;
        }
    }
}
