<?php

declare(strict_types=1);

namespace App\ValueObjects;

use InvalidArgumentException;

final class VideoRegion extends MediaTimestamp
{
    public function __construct(
        private readonly CommentTime $startTime,
        private readonly CommentTime $endTime,
        private readonly float $frameRate,
        private readonly Rectangle $bounds
    ) {
        $this->validateTimes();
        $this->validateFrameRate();
    }

    public static function fromTimes(
        CommentTime $startTime, 
        CommentTime $endTime, 
        float $frameRate, 
        Rectangle $bounds
    ): self {
        return new self($startTime, $endTime, $frameRate, $bounds);
    }

    public static function fromSeconds(
        float $startSeconds, 
        float $endSeconds, 
        float $frameRate, 
        Rectangle $bounds
    ): self {
        return new self(
            CommentTime::fromSeconds($startSeconds),
            CommentTime::fromSeconds($endSeconds),
            $frameRate,
            $bounds
        );
    }

    public static function fromFrames(
        int $startFrame, 
        int $endFrame, 
        float $frameRate, 
        Rectangle $bounds
    ): self {
        return new self(
            CommentTime::fromFrame($startFrame, $frameRate),
            CommentTime::fromFrame($endFrame, $frameRate),
            $frameRate,
            $bounds
        );
    }

    public static function fromArray(array $data): self
    {
        $frameRate = (float) ($data['frame_rate'] ?? $data['media']['frame_rate'] ?? 30.0);
        
        $startTime = isset($data['start_time']) 
            ? CommentTime::fromSeconds((float) $data['start_time'])
            : CommentTime::fromSeconds((float) ($data['timing']['start']['seconds'] ?? 0));

        $endTime = isset($data['end_time'])
            ? CommentTime::fromSeconds((float) $data['end_time'])
            : CommentTime::fromSeconds((float) ($data['timing']['end']['seconds'] ?? 0));

        $bounds = isset($data['bounds']) 
            ? Rectangle::fromArray($data['bounds'])
            : Rectangle::fromArray($data['position'] ?? []);

        return new self($startTime, $endTime, $frameRate, $bounds);
    }

    public function getType(): string
    {
        return 'video_region';
    }

    public function getStartTime(): CommentTime
    {
        return $this->startTime;
    }

    public function getEndTime(): ?CommentTime
    {
        return $this->endTime;
    }

    public function getDuration(): ?CommentTime
    {
        return $this->endTime->subtract($this->startTime);
    }

    public function getFrameRate(): ?float
    {
        return $this->frameRate;
    }

    public function getBounds(): Rectangle
    {
        return $this->bounds;
    }

    public function getStartFrame(): int
    {
        return $this->startTime->getFrame($this->frameRate);
    }

    public function getEndFrame(): int
    {
        return $this->endTime->getFrame($this->frameRate);
    }

    public function getFrameCount(): int
    {
        return $this->getEndFrame() - $this->getStartFrame() + 1;
    }

    public function getFrameAlignedStartTime(): CommentTime
    {
        return $this->startTime->getFrameAlignedTime($this->frameRate);
    }

    public function getFrameAlignedEndTime(): CommentTime
    {
        return $this->endTime->getFrameAlignedTime($this->frameRate);
    }

    public function containsTime(CommentTime $time): bool
    {
        return $time->asSeconds() >= $this->startTime->asSeconds() 
            && $time->asSeconds() <= $this->endTime->asSeconds();
    }

    public function containsFrame(int $frameNumber): bool
    {
        return $frameNumber >= $this->getStartFrame() 
            && $frameNumber <= $this->getEndFrame();
    }

    public function containsPoint(int $x, int $y): bool
    {
        return $this->bounds->contains($x, $y);
    }

    public function overlapsTime(VideoRegion $other): bool
    {
        return $this->startTime->asSeconds() < $other->endTime->asSeconds()
            && $other->startTime->asSeconds() < $this->endTime->asSeconds();
    }

    public function overlapsSpace(VideoRegion $other): bool
    {
        return $this->bounds->overlaps($other->bounds);
    }

    public function overlaps(VideoRegion $other): bool
    {
        return $this->overlapsTime($other) && $this->overlapsSpace($other);
    }

    public function getTimeOverlapDuration(VideoRegion $other): ?CommentTime
    {
        if (!$this->overlapsTime($other)) {
            return null;
        }

        $overlapStart = max($this->startTime->asSeconds(), $other->startTime->asSeconds());
        $overlapEnd = min($this->endTime->asSeconds(), $other->endTime->asSeconds());

        return CommentTime::fromSeconds($overlapEnd - $overlapStart);
    }

    public function expand(CommentTime $timeBuffer, int $spaceBuffer): self
    {
        $newStart = $this->startTime->subtract($timeBuffer);
        $newEnd = $this->endTime->add($timeBuffer);
        
        $newBounds = new Rectangle(
            max(0, $this->bounds->getX() - $spaceBuffer),
            max(0, $this->bounds->getY() - $spaceBuffer),
            $this->bounds->getWidth() + (2 * $spaceBuffer),
            $this->bounds->getHeight() + (2 * $spaceBuffer)
        );

        return new self($newStart, $newEnd, $this->frameRate, $newBounds);
    }

    public function toVideoFrame(CommentTime $time): VideoFrame
    {
        if (!$this->containsTime($time)) {
            throw new InvalidArgumentException('Time is outside this video region');
        }

        return VideoFrame::fromTime($time, $this->frameRate);
    }

    public function toArray(): array
    {
        return [
            'type' => $this->getType(),
            'start_time' => $this->startTime->asSeconds(),
            'end_time' => $this->endTime->asSeconds(),
            'duration' => $this->getDuration()->asSeconds(),
            'frame_rate' => $this->frameRate,
            'bounds' => $this->bounds->toArray(),
            'timing' => [
                'start' => [
                    'seconds' => $this->startTime->asSeconds(),
                    'formatted' => $this->startTime->asFormatted(),
                    'formatted_precise' => $this->startTime->asFormattedPrecise(),
                    'frame' => $this->getStartFrame(),
                    'frame_aligned' => $this->getFrameAlignedStartTime()->asSeconds(),
                ],
                'end' => [
                    'seconds' => $this->endTime->asSeconds(),
                    'formatted' => $this->endTime->asFormatted(),
                    'formatted_precise' => $this->endTime->asFormattedPrecise(),
                    'frame' => $this->getEndFrame(),
                    'frame_aligned' => $this->getFrameAlignedEndTime()->asSeconds(),
                ],
                'duration' => [
                    'seconds' => $this->getDuration()->asSeconds(),
                    'formatted' => $this->getDuration()->asFormatted(),
                    'formatted_precise' => $this->getDuration()->asFormattedPrecise(),
                    'frames' => $this->getFrameCount(),
                ],
            ],
            'position' => $this->bounds->jsonSerialize(),
            'media' => [
                'frame_rate' => $this->frameRate,
                'type' => 'video',
            ],
            'frames' => [
                'start' => $this->getStartFrame(),
                'end' => $this->getEndFrame(),
                'count' => $this->getFrameCount(),
            ],
        ];
    }

    private function validateTimes(): void
    {
        if ($this->startTime->asSeconds() >= $this->endTime->asSeconds()) {
            throw new InvalidArgumentException(
                'Start time must be before end time. Got start: ' . 
                $this->startTime->display() . ', end: ' . $this->endTime->display()
            );
        }
    }

    private function validateFrameRate(): void
    {
        if ($this->frameRate <= 0) {
            throw new InvalidArgumentException('Frame rate must be positive, got: ' . $this->frameRate);
        }

        if (!is_finite($this->frameRate)) {
            throw new InvalidArgumentException('Frame rate must be finite');
        }
    }
}