<?php

declare(strict_types=1);

namespace App\Enums\Deliverable;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum DeliverableFormat: string implements HasLabel, HasDescription
{
    case DESIGN = 'design';
    case VIDEO = 'video';
    case AUDIO = 'audio';
    case DOCUMENT = 'document';

    public function getLabel(): string
    {
        return match ($this) {
            self::DESIGN => 'Design',
            self::VIDEO => 'Video',
            self::AUDIO => 'Audio',
            self::DOCUMENT => 'Document',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::DESIGN => 'Visual design deliverables including graphics, layouts, and UI elements',
            self::VIDEO => 'Video content including promotional videos, tutorials, and presentations',
            self::AUDIO => 'Audio content including podcasts, voiceovers, and sound effects',
            self::DOCUMENT => 'Written content including reports, specifications, and documentation',
        };
    }

    public function getConfigKey(): string
    {
        return "deliverables.{$this->value}";
    }

    public function getAvailableTypes(): array
    {
        return array_keys(config($this->getConfigKey(), []));
    }

    public function getAllowedExtensions(): array
    {
        return match ($this) {
            self::DESIGN => ['png', 'jpg', 'jpeg', 'svg', 'gif', 'webp', 'ai', 'psd', 'sketch', 'fig'],
            self::VIDEO => ['mp4', 'mov', 'avi', 'mkv', 'webm', 'wmv'],
            self::AUDIO => ['mp3', 'wav', 'aac', 'flac', 'm4a', 'ogg'],
            self::DOCUMENT => ['pdf', 'doc', 'docx', 'txt', 'md', 'rtf'],
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::DESIGN => 'heroicon-o-paint-brush',
            self::VIDEO => 'heroicon-o-video-camera',
            self::AUDIO => 'heroicon-o-musical-note',
            self::DOCUMENT => 'heroicon-o-document-text',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DESIGN => 'emerald',
            self::VIDEO => 'red',
            self::AUDIO => 'amber',
            self::DOCUMENT => 'sky',
        };
    }

    public function getMaxFileSize(): int
    {
        return match ($this) {
            self::DESIGN => 50 * 1024 * 1024, // 50MB
            self::VIDEO => 500 * 1024 * 1024, // 500MB
            self::AUDIO => 100 * 1024 * 1024, // 100MB
            self::DOCUMENT => 25 * 1024 * 1024, // 25MB
        };
    }
}