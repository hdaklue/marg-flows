<?php

declare(strict_types=1);

namespace App\Services\Video\Services;

use App\Services\Video\Contracts\ConversionContract;
use App\Services\Video\DTOs\ResolutionData;
use Exception;
use FFMpeg\Filters\Video\ResizeFilter;
use FFMpeg\Format\Video\WebM;
use FFMpeg\Format\Video\WMV;
use FFMpeg\Format\Video\X264;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

final class ResolutionExporter
{
    public function __construct(
        private readonly string $sourcePath,
        private readonly string $disk = 'local',
    ) {}

    public static function start(
        string $sourcePath,
        string $disk = 'local',
    ): self {
        return new self($sourcePath, $disk);
    }

    public function export(
        ConversionContract $conversion,
        string $outputPath,
    ): ResolutionData {
        try {
            // Use FFMpeg directly
            $media = FFMpeg::fromDisk($this->disk)->open($this->sourcePath);

            // Apply resolution conversion using the conversion's format and dimension
            $formatString = $conversion->getFormat();
            $dimension = $conversion->getDimension();
            $bitrate = $conversion->getTargetBitrate();

            // Apply resize filter using FFMpeg's Dimension class
            $media->addFilter(function ($filters) use ($dimension) {
                $ffmpegDimension = new \FFMpeg\Coordinate\Dimension(
                    $dimension->getWidth(),
                    $dimension->getHeight(),
                );

                return $filters->resize(
                    $ffmpegDimension,
                    ResizeFilter::RESIZEMODE_INSET,
                );
            });

            // Create the appropriate FFMpeg format object
            $format = match ($formatString) {
                'mp4', 'mov' => new X264(),
                'webm' => new WebM(),
                'avi' => new WMV(),
                default => new X264(),
            };

            // Set bitrate if specified
            if ($bitrate && method_exists($format, 'setKiloBitrate')) {
                $format->setKiloBitrate($bitrate);
            }

            // Export with the conversion's format
            $media->export()->inFormat($format)->save($outputPath);

            // Get file size using Storage disk path
            $fullStoragePath = Storage::disk($this->disk)->path($outputPath);
            $fileSize = File::exists($fullStoragePath)
                ? File::size($fullStoragePath)
                : 0;

            return ResolutionData::success(
                get_class($conversion),
                $outputPath,
                $fileSize,
            );
        } catch (Exception $e) {
            return ResolutionData::failed(
                get_class($conversion),
                $e->getMessage(),
            );
        }
    }
}
