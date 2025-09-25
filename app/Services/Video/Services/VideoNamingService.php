<?php

declare(strict_types=1);

namespace App\Services\Video\Services;

use App\Services\Video\Contracts\ConversionContract;
use App\Services\Video\Enums\NamingPattern;
use App\Services\Video\Video;
use Carbon\Carbon;
use InvalidArgumentException;

final class VideoNamingService
{
    private NamingPattern $pattern;

    public function __construct(?NamingPattern $pattern = null)
    {
        $this->pattern = $pattern ?? NamingPattern::default();
    }

    public static function timestamped(): self
    {
        return new self(NamingPattern::Timestamped);
    }

    public static function withPattern(NamingPattern $pattern): self
    {
        return new self($pattern);
    }

    public function generateName(Video $video, ConversionContract $conversion): string
    {
        $basename = pathinfo($video->getFilename(), PATHINFO_FILENAME);

        return match ($this->pattern) {
            NamingPattern::Quality => "{$basename}_{$conversion->getQuality()}.{$conversion->getFormat()}",
            NamingPattern::Dimension => "{$basename}_{$conversion
                ->getDimension()
                ?->getWidth()}x{$conversion
                ->getDimension()
                ?->getHeight()}.{$conversion->getFormat()}",
            NamingPattern::Conversion, NamingPattern::ResolutionLabel => "{$basename}_{$conversion->getName()}.{$conversion->getFormat()}",
            NamingPattern::Resolution => "{$basename}_{$this->getResolutionName(
                $conversion,
            )}.{$conversion->getFormat()}",
            default => throw new InvalidArgumentException('Invalid naming pattern'),
        };
    }

    public function generateFilenameFromPattern(Video $video): string
    {
        $basename = pathinfo($video->getFilename(), PATHINFO_FILENAME);
        $ext = $video->getExtension();

        $filename = match ($this->pattern) {
            NamingPattern::Timestamped => "{$basename}_"
                . Carbon::now()->format('YmdHis')
                . ".{$ext}",
            NamingPattern::Dimension => "{$basename}_{$video->getWidth()}x{$video->getHeight()}.{$ext}",
            default => "{$basename}_edited.{$ext}",
        };

        $directory = $video->getDirectory();
        if (! empty($directory) && $directory !== '.') {
            return $directory . DIRECTORY_SEPARATOR . $filename;
        }

        return $filename;
    }

    private function getResolutionName(ConversionContract $conversion): string
    {
        return str_replace([' ', '-'], '_', strtolower($conversion->getName()));
    }
}
