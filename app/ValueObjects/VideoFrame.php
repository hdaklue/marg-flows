<?php

declare(strict_types=1);

namespace App\ValueObjects;

use InvalidArgumentException;

final class VideoFrame extends MediaTimestamp
{
    private readonly int $frameNumber;

    public function __construct(
        private readonly CommentTime $time,
        private readonly float $frameRate,
        null|int $frameNumber = null,
    ) {
        $this->validateFrameRate();
        $this->frameNumber = $frameNumber ?? $this->time->getFrame($frameRate);
    }

    public static function fromTime(CommentTime $time, float $frameRate): self
    {
        return new self($time, $frameRate);
    }

    public static function fromSeconds(float $seconds, float $frameRate): self
    {
        return new self(CommentTime::fromSeconds($seconds), $frameRate);
    }

    public static function fromFrame(int $frameNumber, float $frameRate): self
    {
        $time = CommentTime::fromFrame($frameNumber, $frameRate);

        return new self($time, $frameRate, $frameNumber);
    }

    public static function fromFormatted(string $timeString, float $frameRate): self
    {
        return new self(CommentTime::fromFormatted($timeString), $frameRate);
    }

    public static function fromArray(array $data): self
    {
        $frameRate = (float) ($data['frame_rate'] ?? $data['media']['frame_rate'] ?? 30.0);

        if (isset($data['frame_number'])) {
            return self::fromFrame((int) $data['frame_number'], $frameRate);
        }

        $seconds = (float) ($data['time'] ?? $data['timing']['seconds'] ?? 0);

        return self::fromSeconds($seconds, $frameRate);
    }

    public function getType(): string
    {
        return 'video_frame';
    }

    public function getStartTime(): CommentTime
    {
        return $this->time;
    }

    public function getEndTime(): CommentTime
    {
        return $this->time; // Single frame end time is same as start time
    }

    public function getDuration(): CommentTime
    {
        return CommentTime::fromSeconds(1.0 / $this->frameRate); // Duration of one frame
    }

    public function getFrameRate(): float
    {
        return $this->frameRate;
    }

    public function getFrameNumber(): int
    {
        return $this->frameNumber;
    }

    public function getTime(): CommentTime
    {
        return $this->time;
    }

    public function getFrameAlignedTime(): CommentTime
    {
        return $this->time->getFrameAlignedTime($this->frameRate);
    }

    public function nextFrame(): self
    {
        return self::fromFrame($this->frameNumber + 1, $this->frameRate);
    }

    public function previousFrame(): self
    {
        throw_if(
            $this->frameNumber <= 0,
            new InvalidArgumentException('Cannot go to previous frame from frame 0'),
        );

        return self::fromFrame($this->frameNumber - 1, $this->frameRate);
    }

    public function addFrames(int $frames): self
    {
        $newFrameNumber = $this->frameNumber + $frames;

        throw_if(
            $newFrameNumber < 0,
            new InvalidArgumentException('Cannot have negative frame number'),
        );

        return self::fromFrame($newFrameNumber, $this->frameRate);
    }

    public function isAtSameFrame(VideoFrame $other): bool
    {
        return (
            $this->frameNumber === $other->frameNumber
            && abs($this->frameRate - $other->frameRate) < PHP_FLOAT_EPSILON
        );
    }

    public function frameDifference(VideoFrame $other): int
    {
        throw_if(
            abs($this->frameRate - $other->frameRate) >= PHP_FLOAT_EPSILON,
            new InvalidArgumentException('Cannot compare frames with different frame rates'),
        );

        return abs($this->frameNumber - $other->frameNumber);
    }

    public function toArray(): array
    {
        return [
            'type' => $this->getType(),
            'time' => $this->time->asSeconds(),
            'frame_number' => $this->frameNumber,
            'frame_rate' => $this->frameRate,
            'timing' => [
                'seconds' => $this->time->asSeconds(),
                'formatted' => $this->time->asFormatted(),
                'formatted_precise' => $this->time->asFormattedPrecise(),
                'frame' => $this->frameNumber,
                'frame_aligned' => $this->getFrameAlignedTime()->asSeconds(),
            ],
            'media' => [
                'frame_rate' => $this->frameRate,
                'type' => 'video',
            ],
            'frame' => [
                'number' => $this->frameNumber,
                'duration' => $this->getDuration()->asSeconds(),
                'display' => $this->time->displayWithFrame($this->frameRate),
            ],
        ];
    }

    private function validateFrameRate(): void
    {
        throw_if(
            $this->frameRate <= 0,
            new InvalidArgumentException('Frame rate must be positive, got: ' . $this->frameRate),
        );

        throw_unless(
            is_finite($this->frameRate),
            new InvalidArgumentException('Frame rate must be finite'),
        );
    }
}
