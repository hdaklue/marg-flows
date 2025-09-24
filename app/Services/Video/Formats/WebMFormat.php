<?php

declare(strict_types=1);

namespace App\Services\Video\Formats;

use App\Services\Video\Contracts\VideoFormatContract;
use App\Services\Video\Enums\BitrateEnum;
use FFMpeg\Format\Video\WebM;
use FFMpeg\Format\VideoInterface;

final class WebMFormat implements VideoFormatContract
{
    /**
     * Private constructor to enforce singleton pattern via FormatFactory.
     */
    private function __construct() {}

    /**
     * Internal factory method for FormatFactory use only.
     *
     * @internal
     */
    public static function createInstance(): self
    {
        return new self();
    }

    public function getDriverFormat(null|BitrateEnum $bitrate = null): VideoInterface
    {
        $format = new WebM();

        if ($bitrate) {
            $format->setKiloBitrate($bitrate->getKbps());
        } elseif ($this->getDefaultBitrate()) {
            $format->setKiloBitrate($this->getDefaultBitrate());
        }

        return $format;
    }

    public function getExtension(): string
    {
        return 'webm';
    }

    public function getName(): string
    {
        return 'WebM (VP8/VP9)';
    }

    public function getDefaultBitrate(): null|int
    {
        return BitrateEnum::HIGH_720P->getKbps(); // 2500 kbps - good for web
    }

    public function supportsBitrate(BitrateEnum $bitrate): bool
    {
        // WebM supports all bitrates but works better with web-optimized rates
        return true;
    }
}
