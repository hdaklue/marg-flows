<?php

declare(strict_types=1);

namespace App\ValueObjects\Deliverable;

use InvalidArgumentException;

/**
 * ValueObject for video deliverable specifications.
 */
final class VideoSpecification
{
    private const string TYPE = 'video';

    public function __construct(
        private readonly string $name,
        private readonly int $durationMin,
        private readonly int $durationMax,
        private readonly string $resolution,
        private readonly string $format,
        private readonly string $description,
        private readonly array $tags,
        private readonly array $recommendedPlatforms,
        private readonly array $requirements,
        private readonly array $constraints = [],
        private readonly null|string $codec = null,
        private readonly null|int $bitrate = null,
        private readonly null|int $frameRate = null,
    ) {
        throw_if(
            $this->durationMin < 0 || $this->durationMax < 0,
            new InvalidArgumentException(
                'Duration values must be non-negative.',
            ),
        );

        throw_if(
            $this->durationMin > $this->durationMax,
            new InvalidArgumentException(
                'Minimum duration cannot exceed maximum duration.',
            ),
        );
    }

    public static function fromConfig(array $config): self
    {
        return new self(
            name: $config['name'] ?? 'Unknown Video',
            durationMin: $config['duration_min'] ?? 0,
            durationMax: $config['duration_max'] ?? 0,
            resolution: $config['resolution'] ?? '1920x1080',
            format: $config['format'] ?? 'mp4',
            description: $config['description'] ?? '',
            tags: $config['tags'] ?? [],
            recommendedPlatforms: $config['recommended_platforms'] ?? [],
            requirements: $config['requirements'] ?? [],
            constraints: $config['constraints'] ?? [],
            codec: $config['codec'] ?? null,
            bitrate: $config['bitrate'] ?? null,
            frameRate: $config['frame_rate'] ?? null,
        );
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDurationMin(): int
    {
        return $this->durationMin;
    }

    public function getDurationMax(): int
    {
        return $this->durationMax;
    }

    public function getResolution(): string
    {
        return $this->resolution;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function getRecommendedPlatforms(): array
    {
        return $this->recommendedPlatforms;
    }

    public function getRequirements(): array
    {
        return $this->requirements;
    }

    public function getConstraints(): array
    {
        return $this->constraints;
    }

    public function getCodec(): null|string
    {
        return $this->codec;
    }

    public function getBitrate(): null|int
    {
        return $this->bitrate;
    }

    public function getFrameRate(): null|int
    {
        return $this->frameRate;
    }

    public function getDurationRange(): array
    {
        return [
            'min' => $this->durationMin,
            'max' => $this->durationMax,
            'min_formatted' => $this->formatDuration($this->durationMin),
            'max_formatted' => $this->formatDuration($this->durationMax),
        ];
    }

    public function getResolutionDimensions(): array
    {
        if (str_contains($this->resolution, 'x')) {
            [$width, $height] = explode('x', $this->resolution);

            return [
                'width' => (int) $width,
                'height' => (int) $height,
            ];
        }

        return ['width' => 0, 'height' => 0];
    }

    public function isHD(): bool
    {
        $dimensions = $this->getResolutionDimensions();

        return $dimensions['height'] >= 720;
    }

    public function isFullHD(): bool
    {
        $dimensions = $this->getResolutionDimensions();

        return $dimensions['height'] >= 1080;
    }

    public function is4K(): bool
    {
        $dimensions = $this->getResolutionDimensions();

        return $dimensions['height'] >= 2160;
    }

    public function getQualityLevel(): string
    {
        if ($this->is4K()) {
            return '4K';
        } elseif ($this->isFullHD()) {
            return 'Full HD';
        } elseif ($this->isHD()) {
            return 'HD';
        }

        return 'SD';
    }

    public function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        }

        $minutes = intval($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return $remainingSeconds > 0
                ? "{$minutes}m {$remainingSeconds}s"
                : "{$minutes}m";
        }

        $hours = intval($minutes / 60);
        $remainingMinutes = $minutes % 60;

        $result = "{$hours}h";
        if ($remainingMinutes > 0) {
            $result .= " {$remainingMinutes}m";
        }
        if ($remainingSeconds > 0) {
            $result .= " {$remainingSeconds}s";
        }

        return $result;
    }

    public function validate(array $fileData): bool
    {
        // Validate duration if provided
        if (isset($fileData['duration'])) {
            $duration = (int) $fileData['duration'];
            if (
                $duration < $this->durationMin
                || $duration > $this->durationMax
            ) {
                return false;
            }
        }

        // Validate resolution if provided
        if (isset($fileData['width'], $fileData['height'])) {
            $expectedDimensions = $this->getResolutionDimensions();
            $fileWidth = (int) $fileData['width'];
            $fileHeight = (int) $fileData['height'];

            if (
                $expectedDimensions['width'] > 0
                && $expectedDimensions['height'] > 0
            ) {
                if (
                    $fileWidth !== $expectedDimensions['width']
                    || $fileHeight !== $expectedDimensions['height']
                ) {
                    return false;
                }
            }
        }

        // Validate format if provided
        if (isset($fileData['format'])) {
            $fileFormat = strtolower($fileData['format']);
            if ($fileFormat !== strtolower($this->format)) {
                return false;
            }
        }

        return true;
    }

    public function getValidationRules(): array
    {
        return [
            'duration' => [
                'required',
                'integer',
                'min:' . $this->durationMin,
                'max:' . $this->durationMax,
            ],
            'format' => ['required', 'string', 'in:mp4,mov,avi,mkv,webm,wmv'],
            'resolution' => ['sometimes', 'string'],
        ];
    }

    public function matchesDuration(int $duration): bool
    {
        return (
            $duration >= $this->durationMin
            && $duration <= $this->durationMax
        );
    }

    public function matchesResolution(int $width, int $height): bool
    {
        $expected = $this->getResolutionDimensions();

        return $expected['width'] === $width && $expected['height'] === $height;
    }

    public function getEstimatedFileSize(int $duration): array
    {
        $bitrate = $this->bitrate ?? $this->getEstimatedBitrate();
        $sizeBytes = ($bitrate * $duration) / 8; // Convert bits to bytes

        return [
            'bytes' => $sizeBytes,
            'kb' => $sizeBytes / 1024,
            'mb' => $sizeBytes / (1024 * 1024),
            'gb' => $sizeBytes / (1024 * 1024 * 1024),
            'formatted' => $this->formatFileSize($sizeBytes),
        ];
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'duration_min' => $this->durationMin,
            'duration_max' => $this->durationMax,
            'duration_range' => $this->getDurationRange(),
            'resolution' => $this->resolution,
            'resolution_dimensions' => $this->getResolutionDimensions(),
            'format' => $this->format,
            'description' => $this->description,
            'tags' => $this->tags,
            'recommended_platforms' => $this->recommendedPlatforms,
            'requirements' => $this->requirements,
            'constraints' => $this->constraints,
            'codec' => $this->codec,
            'bitrate' => $this->bitrate,
            'frame_rate' => $this->frameRate,
            'quality_level' => $this->getQualityLevel(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function equals(self $other): bool
    {
        return (
            $this->name === $other->name
            && $this->durationMin === $other->durationMin
            && $this->durationMax === $other->durationMax
            && $this->resolution === $other->resolution
            && $this->format === $other->format
        );
    }

    private function getEstimatedBitrate(): int
    {
        $dimensions = $this->getResolutionDimensions();
        $pixels = $dimensions['width'] * $dimensions['height'];

        // Rough bitrate estimation based on resolution
        return match (true) {
            $pixels >= (3840 * 2160) => 45000000, // 4K: ~45 Mbps
            $pixels >= (1920 * 1080) => 8000000, // Full HD: ~8 Mbps
            $pixels >= (1280 * 720) => 5000000, // HD: ~5 Mbps
            default => 2500000, // SD: ~2.5 Mbps
        };
    }

    private function formatFileSize(float $bytes): string
    {
        if ($bytes >= (1024 * 1024 * 1024)) {
            return round($bytes / (1024 * 1024 * 1024), 1) . ' GB';
        } elseif ($bytes >= (1024 * 1024)) {
            return round($bytes / (1024 * 1024), 1) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return round($bytes) . ' bytes';
    }
}
