<?php

declare(strict_types=1);

namespace App\Services\Video\Formats;

use App\Services\Video\Contracts\VideoFormatContract;
use App\Services\Video\Enums\BitrateEnum;
use FFMpeg\Format\Video\X264;
use FFMpeg\Format\VideoInterface;

final class MP4Format implements VideoFormatContract
{
    /**
     * Private constructor to enforce singleton pattern via FormatFactory.
     */
    private function __construct() {}

    /**
     * Internal factory method for FormatFactory use only.
     * @internal
     */
    public static function createInstance(): self
    {
        return new self();
    }

    public function getDriverFormat(null|BitrateEnum $bitrate = null): VideoInterface {
        $format = new X264();

        if ($bitrate) {
            $format->setKiloBitrate($bitrate->getKbps());
        } elseif ($this->getDefaultBitrate()) {
            $format->setKiloBitrate($this->getDefaultBitrate());
        }

        return $format;
    }

    public function getExtension(): string
    {
        return 'mp4';
    }

    public function getName(): string
    {
        return 'MP4 (H.264)';
    }

    public function getDefaultBitrate(): null|int
    {
        return BitrateEnum::HIGH_1080P->getKbps(); // 4500 kbps - high quality default
    }

    public function supportsBitrate(BitrateEnum $bitrate): bool
    {
        // MP4/X264 supports all bitrates
        return true;
    }
}
