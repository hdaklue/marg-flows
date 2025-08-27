<?php

declare(strict_types=1);

namespace App\Services\Video\Formats;

use App\Services\Video\Contracts\VideoFormatContract;
use App\Services\Video\Enums\BitrateEnum;
use FFMpeg\Format\Video\WMV;
use FFMpeg\Format\VideoInterface;

final class AVIFormat implements VideoFormatContract
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
    public function getDriverFormat(?BitrateEnum $bitrate = null): VideoInterface
    {
        $format = new WMV();
        
        if ($bitrate) {
            $format->setKiloBitrate($bitrate->getKbps());
        } elseif ($this->getDefaultBitrate()) {
            $format->setKiloBitrate($this->getDefaultBitrate());
        }
        
        return $format;
    }

    public function getExtension(): string
    {
        return 'avi';
    }

    public function getName(): string
    {
        return 'AVI (WMV)';
    }

    public function getDefaultBitrate(): ?int
    {
        return BitrateEnum::HIGH_1080P->getKbps(); // 4500 kbps
    }

    public function supportsBitrate(BitrateEnum $bitrate): bool
    {
        // AVI/WMV supports most bitrates
        return true;
    }
}