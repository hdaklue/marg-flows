<?php

declare(strict_types=1);

namespace App\Support;

use Symfony\Component\Mime\MimeTypes;

/**
 * File type utility class for handling file formats, MIME types, and validation.
 *
 * Provides methods for detecting file types, getting supported formats for web streaming,
 * and generating validation strings for both Laravel and JavaScript.
 */
final class FileTypes
{
    private static ?MimeTypes $mimeTypes = null;

    /**
     * Get the MIME type for a given filename.
     */
    public static function getMimeType(string $filename): ?string
    {
        return self::getMimeTypes()->guessMimeType($filename);
    }

    /**
     * Get the primary file extension for a given MIME type.
     */
    public static function getExtension(string $mimeType): ?string
    {
        $extensions = self::getMimeTypes()->getExtensions($mimeType);

        return $extensions[0] ?? null;
    }

    /**
     * Get all possible file extensions for a given MIME type.
     *
     * @return array<string>
     */
    public static function getExtensions(string $mimeType): array
    {
        return self::getMimeTypes()->getExtensions($mimeType);
    }

    /**
     * Check if the file is an image.
     */
    public static function isImage(string $filename): bool
    {
        $mimeType = self::getMimeType($filename);

        return $mimeType !== null && str_starts_with($mimeType, 'image/');
    }

    /**
     * Check if the file is a video.
     */
    public static function isVideo(string $filename): bool
    {
        $mimeType = self::getMimeType($filename);

        return $mimeType !== null && str_starts_with($mimeType, 'video/');
    }

    /**
     * Check if the file is an audio file.
     */
    public static function isAudio(string $filename): bool
    {
        $mimeType = self::getMimeType($filename);

        return $mimeType !== null && str_starts_with($mimeType, 'audio/');
    }

    /**
     * Check if the file is a PDF document.
     */
    public static function isPdf(string $filename): bool
    {
        return self::getMimeType($filename) === 'application/pdf';
    }

    /**
     * Check if the file is a document (PDF, Word, Excel, PowerPoint, etc.).
     */
    public static function isDocument(string $filename): bool
    {
        $mimeType = self::getMimeType($filename);

        if ($mimeType === null) {
            return false;
        }

        $documentMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv',
            'text/rtf',
        ];

        return in_array($mimeType, $documentMimes, true);
    }

    /**
     * Check if the file is an archive (ZIP, RAR, 7Z, etc.).
     */
    public static function isArchive(string $filename): bool
    {
        $mimeType = self::getMimeType($filename);

        if ($mimeType === null) {
            return false;
        }

        $archiveMimes = [
            'application/zip',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
            'application/gzip',
            'application/x-tar',
        ];

        return in_array($mimeType, $archiveMimes, true);
    }

    /**
     * Get the general category of a file (image, video, audio, document, archive, other).
     */
    public static function getCategory(string $filename): string
    {
        if (self::isImage($filename)) {
            return 'image';
        }

        if (self::isVideo($filename)) {
            return 'video';
        }

        if (self::isAudio($filename)) {
            return 'audio';
        }

        if (self::isDocument($filename)) {
            return 'document';
        }

        if (self::isArchive($filename)) {
            return 'archive';
        }

        return 'other';
    }

    /**
     * Get video formats supported by video.js and HTML5 video elements.
     *
     * @return array<string, string> Extension => MIME type pairs
     */
    public static function getStreamVideoFormats(): array
    {
        return [
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'ogg' => 'video/ogg',
        ];
    }

    /**
     * Get stream video formats as comma-separated MIME types for JavaScript file inputs.
     */
    public static function getStreamVideoFormatsAsValidationString(): string
    {
        return implode(',', self::getStreamVideoFormats());
    }

    /**
     * Get stream audio formats as comma-separated MIME types for JavaScript file inputs.
     */
    public static function getStreamAudioFormatsAsValidationString(): string
    {
        return implode(',', self::getStreamAudioFormats());
    }

    /**
     * Get web image formats as comma-separated MIME types for JavaScript file inputs.
     */
    public static function getWebImageFormatsAsValidationString(): string
    {
        return implode(',', self::getWebImageFormats());
    }

    /**
     * Get file extensions for stream video formats.
     *
     * @return array<string>
     */
    public static function getStreamVideoExtensions(): array
    {
        return array_keys(self::getStreamVideoFormats());
    }

    /**
     * Get file extensions for stream audio formats.
     *
     * @return array<string>
     */
    public static function getStreamAudioExtensions(): array
    {
        return array_keys(self::getStreamAudioFormats());
    }

    /**
     * Get file extensions for web image formats.
     *
     * @return array<string>
     */
    public static function getWebImageExtensions(): array
    {
        return array_keys(self::getWebImageFormats());
    }

    /**
     * Get Laravel validation rule string for stream video formats.
     */
    public static function getStreamVideoForLaravelValidation(): string
    {
        return 'mimes:' . implode(',', self::getStreamVideoExtensions());
    }

    /**
     * Get Laravel validation rule string for stream audio formats.
     */
    public static function getStreamAudioForLaravelValidation(): string
    {
        return 'mimes:' . implode(',', self::getStreamAudioExtensions());
    }

    /**
     * Get Laravel validation rule string for web image formats.
     */
    public static function getWebImageForLaravelValidation(): string
    {
        return 'mimes:' . implode(',', self::getWebImageExtensions());
    }

    public static function getStreamAudioFormats(): array
    {
        return [
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            'aac' => 'audio/aac',
        ];
    }

    public static function getWebImageFormats(): array
    {
        return [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'avif' => 'image/avif',
            'heic' => 'image/heic',
            'tiff' => 'image/tiff',
            'svg' => 'image/svg+xml',
        ];
    }

    public static function isStreamableVideo(string $filename): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return array_key_exists($extension, self::getStreamVideoFormats());
    }

    public static function isStreamableAudio(string $filename): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return array_key_exists($extension, self::getStreamAudioFormats());
    }

    public static function isWebImage(string $filename): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return array_key_exists($extension, self::getWebImageFormats());
    }

    public static function getFormatsByCategory(string $category): array
    {
        return match ($category) {
            'streamVideo' => self::getStreamVideoFormats(),
            'streamAudio' => self::getStreamAudioFormats(),
            'webImage' => self::getWebImageFormats(),
            default => [],
        };
    }

    private static function getMimeTypes(): MimeTypes
    {
        return self::$mimeTypes ??= new MimeTypes;
    }
}
