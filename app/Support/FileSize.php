<?php

declare(strict_types=1);

namespace App\Support;

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

    public static function format(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return number_format($bytes / (1024 * 1024 * 1024), 2) . ' GB';
        }

        if ($bytes >= 1024 * 1024) {
            return number_format($bytes / (1024 * 1024), 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }
}
