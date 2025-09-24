<?php

declare(strict_types=1);

namespace App\ValueObjects;

use InvalidArgumentException;

final class AudioRegion extends MediaTimestamp
{
    public function __construct(
        private readonly CommentTime $startTime,
        private readonly CommentTime $endTime,
    ) {
        $this->validateTimes();
    }

    public static function fromTimes(CommentTime $startTime, CommentTime $endTime): self
    {
        return new self($startTime, $endTime);
    }

    public static function fromSeconds(float $startSeconds, float $endSeconds): self
    {
        return new self(
            CommentTime::fromSeconds($startSeconds),
            CommentTime::fromSeconds($endSeconds),
        );
    }

    public static function fromFormatted(string $startTime, string $endTime): self
    {
        return new self(
            CommentTime::fromFormatted($startTime),
            CommentTime::fromFormatted($endTime),
        );
    }

    public static function fromArray(array $data): self
    {
        $startTime = isset($data['start_time'])
            ? CommentTime::fromSeconds((float) $data['start_time'])
            : CommentTime::fromSeconds((float) ($data['timing']['start']['seconds'] ?? 0));

        $endTime = isset($data['end_time'])
            ? CommentTime::fromSeconds((float) $data['end_time'])
            : CommentTime::fromSeconds((float) ($data['timing']['end']['seconds'] ?? 0));

        return new self($startTime, $endTime);
    }

    public function getType(): string
    {
        return 'audio_region';
    }

    public function getStartTime(): CommentTime
    {
        return $this->startTime;
    }

    public function getEndTime(): CommentTime
    {
        return $this->endTime;
    }

    public function getDuration(): CommentTime
    {
        return $this->endTime->subtract($this->startTime);
    }

    public function getFrameRate(): null|float
    {
        return null; // Audio regions don't have frame rates
    }

    public function contains(CommentTime $time): bool
    {
        return (
            $time->asSeconds() >= $this->startTime->asSeconds()
            && $time->asSeconds() <= $this->endTime->asSeconds()
        );
    }

    public function overlaps(AudioRegion $other): bool
    {
        return (
            $this->startTime->asSeconds() < $other->endTime->asSeconds()
            && $other->startTime->asSeconds() < $this->endTime->asSeconds()
        );
    }

    public function getOverlapDuration(AudioRegion $other): null|CommentTime
    {
        if (!$this->overlaps($other)) {
            return null;
        }

        $overlapStart = max($this->startTime->asSeconds(), $other->startTime->asSeconds());
        $overlapEnd = min($this->endTime->asSeconds(), $other->endTime->asSeconds());

        return CommentTime::fromSeconds($overlapEnd - $overlapStart);
    }

    public function expand(CommentTime $beforeBuffer, CommentTime $afterBuffer): self
    {
        $newStart = $this->startTime->subtract($beforeBuffer);
        $newEnd = $this->endTime->add($afterBuffer);

        return new self($newStart, $newEnd);
    }

    public function toArray(): array
    {
        return [
            'type' => $this->getType(),
            'start_time' => $this->startTime->asSeconds(),
            'end_time' => $this->endTime->asSeconds(),
            'duration' => $this->getDuration()->asSeconds(),
            'timing' => [
                'start' => [
                    'seconds' => $this->startTime->asSeconds(),
                    'formatted' => $this->startTime->asFormatted(),
                    'formatted_precise' => $this->startTime->asFormattedPrecise(),
                ],
                'end' => [
                    'seconds' => $this->endTime->asSeconds(),
                    'formatted' => $this->endTime->asFormatted(),
                    'formatted_precise' => $this->endTime->asFormattedPrecise(),
                ],
                'duration' => [
                    'seconds' => $this->getDuration()->asSeconds(),
                    'formatted' => $this->getDuration()->asFormatted(),
                    'formatted_precise' => $this->getDuration()->asFormattedPrecise(),
                ],
            ],
        ];
    }

    private function validateTimes(): void
    {
        throw_if(
            $this->startTime->asSeconds() >= $this->endTime->asSeconds(),
            new InvalidArgumentException(
                'Start time must be before end time. Got start: '
                . $this->startTime->display()
                . ', end: '
                . $this->endTime->display(),
            ),
        );
    }
}
