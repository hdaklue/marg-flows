<?php

declare(strict_types=1);

namespace App\Services\Video\Formats;

use App\Services\Video\Contracts\VideoFormatContract;
use App\Services\Video\Enums\BitrateEnum;
use FFMpeg\Format\Video\X264;
use FFMpeg\Format\VideoInterface;

final class MOVFormat implements VideoFormatContract
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
        return 'mov';
    }

    public function getName(): string
    {
        return 'QuickTime (MOV)';
    }

    public function getDefaultBitrate(): ?int
    {
        return BitrateEnum::VERY_HIGH_2K->getKbps(); // 12000 kbps - high quality for MOV
    }

    public function supportsBitrate(BitrateEnum $bitrate): bool
    {
        // MOV supports all bitrates, typically used for high-quality video
        return true;
    }
}