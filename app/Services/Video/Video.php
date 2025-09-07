<?php

declare(strict_types=1);

namespace App\Services\Video;

use App\Services\Video\Services\VideoEditor;
use App\Services\Video\ValueObjects\Dimension;
use App\Support\FileSize;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

final class Video
{
    private null|Dimension $dimension = null;

    private null|float $duration = null;

    private null|int $bitrate = null;

    private null|string $extension = null;

    private null|string $relativePath = null;

    private null|string $directory = null;

    private null|string $filename = null;

    private null|int $fileSize = null;

    public function __construct(
        private readonly string $path,
        private readonly bool $isUrl = false,
        private readonly string $disk = 'local',
    ) {
        $this->validatePath();
        $this->extension = $this->extractExtension();
        $this->extractPathComponents();
    }

    /**
     * Load a video from file path.
     */
    public static function load(
        string $path,
        string $disk = 'local',
    ): VideoEditor {
        $video = new self($path, false, $disk);

        return new VideoEditor($video);
    }

    /**
     * Load a video from URL.
     */
    public static function loadFromUrl(
        string $url,
        string $disk = 'local',
    ): VideoEditor {
        $video = new self($url, true, $disk);

        return new VideoEditor($video);
    }

    /**
     * Create a video editor instance from an existing video path.
     */
    public static function make(
        string $path,
        string $disk = 'local',
    ): VideoEditor {
        return self::load($path, $disk);
    }

    /**
     * Create a video object from file path.
     */
    public static function fromPath(string $path, string $disk = 'local'): self
    {
        return new self($path, false, $disk);
    }

    /**
     * Create a video object from URL.
     */
    public static function fromUrl(string $url, string $disk = 'local'): self
    {
        return new self($url, true, $disk);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getDisk(): string
    {
        return $this->disk;
    }

    public function isUrl(): bool
    {
        return $this->isUrl;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function getRelativePath(): string
    {
        return $this->relativePath ?? $this->path;
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getDimension(): Dimension
    {
        if ($this->dimension === null) {
            $this->loadVideoMetadata();
        }

        return $this->dimension;
    }

    public function getWidth(): int
    {
        return $this->getDimension()->getWidth();
    }

    public function getHeight(): int
    {
        return $this->getDimension()->getHeight();
    }

    public function getAspectRatio()
    {
        return $this->getDimension()->getAspectRatio();
    }

    public function getOrientation(): string
    {
        return $this->getDimension()->getOrientation();
    }

    public function getDuration(): float
    {
        if ($this->duration === null) {
            $this->loadVideoMetadata();
        }

        return $this->duration;
    }

    public function getBitrate(): null|int
    {
        if ($this->bitrate === null) {
            $this->loadVideoMetadata();
        }

        return $this->bitrate;
    }

    public function getFileNameWithoutExt(): string
    {
        return str($this->getFilename())->beforeLast('.')->snake()->toString();
    }

    public function isLandscape(): bool
    {
        return $this->getDimension()->isLandscape();
    }

    public function isPortrait(): bool
    {
        return $this->getDimension()->isPortrait();
    }

    public function isSquare(): bool
    {
        return $this->getDimension()->isSquare();
    }

    public function getFileSize(): int
    {
        if ($this->fileSize === null) {
            $this->loadFileSize();
        }

        return $this->fileSize;
    }

    public function getFileSizeFormatted(int $precision = 2): string
    {
        return FileSize::format($this->getFileSize(), $precision);
    }

    public function getFileSizeInMB(int $precision = 2): float
    {
        return round(FileSize::toMB($this->getFileSize()), $precision);
    }

    public function getFileSizeInGB(int $precision = 3): float
    {
        return round(FileSize::toGB($this->getFileSize()), $precision);
    }

    public function getFileSizeInKB(int $precision = 2): float
    {
        return round(FileSize::toKB($this->getFileSize()), $precision);
    }

    // Decimal (base 10) file sizes - matches Finder/Windows Explorer
    public function getFileSizeInMBDecimal(int $precision = 2): float
    {
        return round(FileSize::toMBDecimal($this->getFileSize()), $precision);
    }

    public function getFileSizeInGBDecimal(int $precision = 3): float
    {
        return round(FileSize::toGBDecimal($this->getFileSize()), $precision);
    }

    public function getFileSizeInKBDecimal(int $precision = 2): float
    {
        return round(FileSize::toKBDecimal($this->getFileSize()), $precision);
    }

    /**
     * Get all video metadata as an array.
     */
    public function getMetadata(): array
    {
        // Ensure all metadata is loaded
        $this->loadVideoMetadata();

        return [
            'path' => $this->getPath(),
            'relativePath' => $this->getRelativePath(),
            'directory' => $this->getDirectory(),
            'filename' => $this->getFilename(),
            'extension' => $this->getExtension(),
            'disk' => $this->getDisk(),
            'isUrl' => $this->isUrl(),
            'fileSize' => [
                'bytes' => $this->getFileSize(),
                'formatted' => $this->getFileSizeFormatted(),
                'binary' => [
                    'kb' => $this->getFileSizeInKB(2),
                    'mb' => $this->getFileSizeInMB(2),
                    'gb' => $this->getFileSizeInGB(3),
                ],
                'decimal' => [
                    'kb' => $this->getFileSizeInKBDecimal(2),
                    'mb' => $this->getFileSizeInMBDecimal(2),
                    'gb' => $this->getFileSizeInGBDecimal(3),
                ],
            ],
            'dimension' => $this->getDimension()->toArray(),
            'orientation' => $this->getOrientation(),
            'duration' => $this->getDuration(),
            'bitrate' => $this->getBitrate(),
            'isLandscape' => $this->isLandscape(),
            'isPortrait' => $this->isPortrait(),
            'isSquare' => $this->isSquare(),
        ];
    }

    private function validatePath(): void
    {
        if ($this->isUrl) {
            if (!filter_var($this->path, FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException(
                    "Invalid URL: {$this->path}",
                );
            }
        } else {
            if (!Storage::disk($this->disk)->exists($this->path)) {
                throw new InvalidArgumentException(
                    "Video file not found: {$this->path} on disk: {$this->disk}",
                );
            }
        }
    }

    private function extractExtension(): string
    {
        $pathInfo = pathinfo($this->path);

        return strtolower($pathInfo['extension'] ?? '');
    }

    private function extractPathComponents(): void
    {
        if ($this->isUrl) {
            // For URLs, we can't extract meaningful directory/filename info
            $this->directory = '';
            $this->filename = basename($this->path);
            $this->relativePath = $this->path;

            return;
        }

        $pathInfo = pathinfo($this->path);

        $this->directory = $pathInfo['dirname'];
        $this->filename = $pathInfo['basename'] ?? '';

        // Create relative path by removing the current working directory
        $cwd = getcwd();
        if ($cwd && strpos($this->path, $cwd) === 0) {
            $this->relativePath = ltrim(
                substr($this->path, strlen($cwd)),
                DIRECTORY_SEPARATOR,
            );
        } else {
            $this->relativePath = $this->path;
        }
    }

    private function loadVideoMetadata(): void
    {
        if (
            $this->dimension !== null
            && $this->duration !== null
            && $this->bitrate !== null
        ) {
            return; // Already loaded
        }

        try {
            $media = FFMpeg::fromDisk($this->disk)->open($this->path);

            // Get dimensions
            if ($this->dimension === null) {
                $ffmpegDimensions = $media->getVideoStream()->getDimensions();
                $this->dimension = Dimension::from(
                    $ffmpegDimensions->getWidth(),
                    $ffmpegDimensions->getHeight(),
                );
            }

            // Get duration
            if ($this->duration === null) {
                $this->duration = $media->getDurationInSeconds();
            }

            // Get bitrate
            if ($this->bitrate === null) {
                try {
                    $format = $media->getFormat();
                    if (
                        method_exists($format, 'get')
                        && $format->get('bit_rate')
                    ) {
                        $this->bitrate = (int) ($format->get('bit_rate') / 1000); // Convert to kbps
                    }
                } catch (Exception $e) {
                    $this->bitrate = null; // Could not determine bitrate
                }
            }
        } catch (Exception $e) {
            throw new InvalidArgumentException(
                "Could not analyze video: {$this->path}. Error: "
                . $e->getMessage(),
            );
        }
    }

    private function loadFileSize(): void
    {
        if ($this->fileSize !== null) {
            return; // Already loaded
        }

        try {
            if ($this->isUrl) {
                // For URLs, we cannot easily determine file size without downloading
                $this->fileSize = 0;
            } else {
                $this->fileSize = Storage::disk($this->disk)->size($this->path);
            }
        } catch (Exception $e) {
            // If we cannot determine file size, default to 0
            $this->fileSize = 0;
        }
    }
}
