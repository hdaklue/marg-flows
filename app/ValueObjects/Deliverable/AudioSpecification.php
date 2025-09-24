<?php

declare(strict_types=1);

namespace App\ValueObjects\Deliverable;

use App\Contracts\Deliverables\DeliverableSpecification;
use InvalidArgumentException;

/**
 * ValueObject for audio deliverable specifications.
 */
final class AudioSpecification implements DeliverableSpecification
{
    private const string TYPE = 'audio';

    public function __construct(
        private readonly string $name,
        private readonly int $durationMin,
        private readonly int $durationMax,
        private readonly string $format,
        private readonly string $bitrate,
        private readonly string $sampleRate,
        private readonly string $description,
        private readonly array $tags,
        private readonly array $requirements,
        private readonly array $constraints = [],
        private readonly null|int $channels = null,
        private readonly null|string $codec = null,
        private readonly null|bool $stereo = null,
    ) {
        throw_if(
            $this->durationMin < 0 || $this->durationMax < 0,
            new InvalidArgumentException('Duration values must be non-negative.'),
        );

        throw_if(
            $this->durationMin > $this->durationMax,
            new InvalidArgumentException('Minimum duration cannot exceed maximum duration.'),
        );
    }

    public static function fromConfig(array $config): self
    {
        return new self(
            name: $config['name'] ?? 'Unknown Audio',
            durationMin: $config['duration_min'] ?? 0,
            durationMax: $config['duration_max'] ?? 0,
            format: $config['format'] ?? 'mp3',
            bitrate: $config['bitrate'] ?? '128kbps',
            sampleRate: $config['sample_rate'] ?? '44100Hz',
            description: $config['description'] ?? '',
            tags: $config['tags'] ?? [],
            requirements: $config['requirements'] ?? [],
            constraints: $config['constraints'] ?? [],
            channels: $config['channels'] ?? null,
            codec: $config['codec'] ?? null,
            stereo: $config['stereo'] ?? null,
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

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getBitrate(): string
    {
        return $this->bitrate;
    }

    public function getSampleRate(): string
    {
        return $this->sampleRate;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function getRequirements(): array
    {
        return $this->requirements;
    }

    public function getConstraints(): array
    {
        return $this->constraints;
    }

    public function getChannels(): null|int
    {
        return $this->channels;
    }

    public function getCodec(): null|string
    {
        return $this->codec;
    }

    public function isStereo(): null|bool
    {
        return $this->stereo;
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

    public function getBitrateNumeric(): int
    {
        // Extract numeric value from bitrate string (e.g., "128kbps" -> 128)
        return (int) filter_var($this->bitrate, FILTER_SANITIZE_NUMBER_INT);
    }

    public function getSampleRateNumeric(): int
    {
        // Extract numeric value from sample rate string (e.g., "44100Hz" -> 44100)
        return (int) filter_var($this->sampleRate, FILTER_SANITIZE_NUMBER_INT);
    }

    public function getQualityLevel(): string
    {
        $bitrate = $this->getBitrateNumeric();

        return match (true) {
            $bitrate >= 320 => 'Studio Quality',
            $bitrate >= 256 => 'High Quality',
            $bitrate >= 192 => 'CD Quality',
            $bitrate >= 128 => 'Standard Quality',
            $bitrate >= 96 => 'Good Quality',
            default => 'Basic Quality',
        };
    }

    public function isHighQuality(): bool
    {
        return $this->getBitrateNumeric() >= 256;
    }

    public function isLossless(): bool
    {
        return in_array(strtolower($this->format), ['flac', 'wav', 'aiff'], true);
    }

    public function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        }

        $minutes = intval($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return $remainingSeconds > 0 ? "{$minutes}m {$remainingSeconds}s" : "{$minutes}m";
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
            if ($duration < $this->durationMin || $duration > $this->durationMax) {
                return false;
            }
        }

        // Validate format if provided
        if (isset($fileData['format'])) {
            $fileFormat = strtolower($fileData['format']);
            if ($fileFormat !== strtolower($this->format)) {
                return false;
            }
        }

        // Validate bitrate if provided
        if (isset($fileData['bitrate'])) {
            $fileBitrate = (int) $fileData['bitrate'];
            $expectedBitrate = $this->getBitrateNumeric();

            // Allow some tolerance (within 20% of expected bitrate)
            $tolerance = $expectedBitrate * 0.2;
            if (abs($fileBitrate - $expectedBitrate) > $tolerance) {
                return false;
            }
        }

        // Validate sample rate if provided
        if (isset($fileData['sample_rate'])) {
            $fileSampleRate = (int) $fileData['sample_rate'];
            $expectedSampleRate = $this->getSampleRateNumeric();

            if ($fileSampleRate !== $expectedSampleRate) {
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
            'format' => ['required', 'string', 'in:mp3,wav,aac,flac,m4a,ogg'],
            'bitrate' => ['sometimes', 'integer'],
            'sample_rate' => ['sometimes', 'integer'],
        ];
    }

    public function matchesDuration(int $duration): bool
    {
        return $duration >= $this->durationMin && $duration <= $this->durationMax;
    }

    public function getEstimatedFileSize(int $duration): array
    {
        $bitrate = $this->getBitrateNumeric() * 1000; // Convert to bits per second
        $sizeBytes = ($bitrate * $duration) / 8; // Convert bits to bytes

        return [
            'bytes' => $sizeBytes,
            'kb' => $sizeBytes / 1024,
            'mb' => $sizeBytes / (1024 * 1024),
            'gb' => $sizeBytes / (1024 * 1024 * 1024),
            'formatted' => $this->formatFileSize($sizeBytes),
        ];
    }

    public function hasRequirement(string $requirement): bool
    {
        return (
            isset($this->requirements[$requirement])
            || in_array($requirement, $this->requirements, true)
        );
    }

    public function getChannelConfiguration(): string
    {
        if ($this->channels === 1) {
            return 'Mono';
        } elseif ($this->channels === 2 || $this->stereo === true) {
            return 'Stereo';
        } elseif ($this->channels && $this->channels > 2) {
            return "{$this->channels} Channel";
        }

        return 'Unknown';
    }

    public function getAspectRatio(): float
    {
        // Audio doesn't have aspect ratio, return 1:1 as default
        return 1.0;
    }

    public function getAspectRatioName(): string
    {
        // Audio doesn't have aspect ratio
        return 'N/A';
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'duration_min' => $this->durationMin,
            'duration_max' => $this->durationMax,
            'duration_range' => $this->getDurationRange(),
            'format' => $this->format,
            'bitrate' => $this->bitrate,
            'bitrate_numeric' => $this->getBitrateNumeric(),
            'sample_rate' => $this->sampleRate,
            'sample_rate_numeric' => $this->getSampleRateNumeric(),
            'description' => $this->description,
            'tags' => $this->tags,
            'requirements' => $this->requirements,
            'constraints' => $this->constraints,
            'channels' => $this->channels,
            'codec' => $this->codec,
            'stereo' => $this->stereo,
            'quality_level' => $this->getQualityLevel(),
            'is_high_quality' => $this->isHighQuality(),
            'is_lossless' => $this->isLossless(),
            'channel_configuration' => $this->getChannelConfiguration(),
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
            && $this->format === $other->format
            && $this->bitrate === $other->bitrate
            && $this->sampleRate === $other->sampleRate
        );
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
