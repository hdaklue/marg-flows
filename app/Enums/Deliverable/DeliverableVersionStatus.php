<?php

declare(strict_types=1);

namespace App\Enums\Deliverable;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DeliverableVersionStatus: string implements HasColor, HasLabel
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case REVISION_NEEDED = 'revision_needed';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Submitted',
            self::REVISION_NEEDED => 'Revision Needed',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT => 'zinc',
            self::SUBMITTED => 'sky',
            self::REVISION_NEEDED => 'amber',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::DRAFT => 'Version is being prepared, not yet submitted',
            self::SUBMITTED => 'Version has been submitted for review',
            self::REVISION_NEEDED => 'Version needs to be revised based on feedback',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::DRAFT => 'heroicon-o-pencil',
            self::SUBMITTED => 'heroicon-o-paper-airplane',
            self::REVISION_NEEDED => 'heroicon-o-exclamation-triangle',
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return match ([$this, $newStatus]) {
            // From DRAFT
            [self::DRAFT, self::SUBMITTED] => true,
            // From SUBMITTED
            [self::SUBMITTED, self::REVISION_NEEDED] => true,
            [self::SUBMITTED, self::DRAFT] => true, // Back to draft if needed
            // From REVISION_NEEDED
            [self::REVISION_NEEDED, self::DRAFT] => true,
            [self::REVISION_NEEDED, self::SUBMITTED] => true, // Resubmit after revision
            default => false,
        };
    }

    public function isEditable(): bool
    {
        return $this === self::DRAFT || $this === self::REVISION_NEEDED;
    }

    public function isSubmitted(): bool
    {
        return $this === self::SUBMITTED;
    }

    public function needsRevision(): bool
    {
        return $this === self::REVISION_NEEDED;
    }
}
