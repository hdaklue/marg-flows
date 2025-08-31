<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Number;

final class FileSize
{
    public static function fromMB(float $megabytes): int
    {
        return (int) ($megabytes * 1024 * 1024);
    }

    public static function fromGB(float $gigabytes): int
    {
        return (int) ($gigabytes * 1024 * 1024 * 1024);
    }

    public static function fromKB(float $kilobytes): int
    {
        return (int) ($kilobytes * 1024);
    }

    public static function toMB(int $bytes): float
    {
        return $bytes / (1024 * 1024);
    }

    public static function toGB(int $bytes): float
    {
        return $bytes / (1024 * 1024 * 1024);
    }

    public static function toKB(int $bytes): float
    {
        return $bytes / 1024;
    }

    // Decimal (base 10) conversions - matches Finder/Windows Explorer
    public static function toMBDecimal(int $bytes): float
    {
        return $bytes / (1000 * 1000);
    }

    public static function toGBDecimal(int $bytes): float
    {
        return $bytes / (1000 * 1000 * 1000);

    }

    public static function toKBDecimal(int $bytes): float
    {
        return $bytes / 1000;
    }

    public static function format(int $bytes, int $precision = 3): string
    {
        return Number::fileSize($bytes, $precision);

    }
}
