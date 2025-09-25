<?php

declare(strict_types=1);

namespace App\Services\Video\Enums;

enum NamingPattern: string
{
    case Quality = '{basename}_{quality}.{ext}';
    case Dimension = '{basename}_{width}x{height}.{ext}';
    case Format = '{basename}_{format}.{ext}';
    case Full = '{basename}_{width}x{height}_{quality}_{timestamp}.{ext}';
    case Conversion = '{basename}_{conversion_name}.{ext}';
    case Detailed = '{basename}_{width}x{height}_{quality}_{bitrate}kbps.{ext}';
    case Timestamped = '{basename}_{timestamp}.{ext}';
    case Simple = '{basename}_converted.{ext}';
    case Resolution = '{basename}_{resolution_name}.{ext}';
    case ResolutionLabel = '{basename}_{resolution_label}.{ext}';
    case AspectRatio = '{basename}_{aspect_ratio}.{ext}';

    public static function default(): self
    {
        return self::Conversion;
    }

    public static function getPatternOptions(): array
    {
        return array_map(fn (self $pattern) => [
            'value' => $pattern->value,
            'label' => $pattern->name,
            'description' => $pattern->getDescription(),
        ], self::cases());
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Quality => 'Filename with quality suffix (e.g., video_high.mp4)',
            self::Dimension => 'Filename with dimensions (e.g., video_1920x1080.mp4)',
            self::Format => 'Filename with format (e.g., video_webm.webm)',
            self::Full => 'Full details with timestamp (e.g., video_1920x1080_high_20241224123456.mp4)',
            self::Conversion => 'Filename with conversion name (e.g., video_4K.mp4)',
            self::Detailed => 'Detailed with bitrate (e.g., video_1920x1080_high_5000kbps.mp4)',
            self::Timestamped => 'Filename with timestamp (e.g., video_20241224123456.mp4)',
            self::Simple => 'Simple converted suffix (e.g., video_converted.mp4)',
            self::Resolution => 'Filename with resolution name (e.g., video_full_hd.mp4)',
            self::ResolutionLabel => 'Filename with resolution label (e.g., video_720p.mp4)',
            self::AspectRatio => 'Filename with aspect ratio (e.g., video_16-9.mp4)',
        };
    }

    public function getPattern(): string
    {
        return $this->value;
    }
}
