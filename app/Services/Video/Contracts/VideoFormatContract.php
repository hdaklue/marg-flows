<?php

declare(strict_types=1);

namespace App\Services\Video\Contracts;

use App\Services\Video\Enums\BitrateEnum;
use FFMpeg\Format\VideoInterface;

interface VideoFormatContract
{
    /**
     * Get the underlying FFMpeg format driver.
     */
    public function getDriverFormat(null|BitrateEnum $bitrate = null): VideoInterface;

    /**
     * Get the file extension for this format.
     */
    public function getExtension(): string;

    /**
     * Get a human-readable name for this format.
     */
    public function getName(): string;

    /**
     * Get the default bitrate for this format if none specified.
     */
    public function getDefaultBitrate(): null|int;

    /**
     * Check if this format supports the given bitrate.
     */
    public function supportsBitrate(BitrateEnum $bitrate): bool;
}
