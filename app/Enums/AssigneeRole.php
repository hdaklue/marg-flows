<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum AssigneeRole: string implements HasDescription, HasLabel
{
    case ASSIGNEE = 'assignee';
    case APPROVER = 'approver';
    case REVIEWER = 'reviewer';
    case OBSERVER = 'observer';

    public function getLevel(): int
    {
        return match ($this) {
            self::OBSERVER => 1,
            self::REVIEWER => 2,
            self::ASSIGNEE => 3,
            self::APPROVER => 4,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::ASSIGNEE => __('app.roles.assignee'),
            self::APPROVER => __('app.roles.approver'),
            self::REVIEWER => __('app.roles.reviewer'),
            self::OBSERVER => __('app.roles.observer'),
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::ASSIGNEE => __('app.role_descriptions.assignee'),
            self::APPROVER => __('app.role_descriptions.approver'),
            self::REVIEWER => __('app.role_descriptions.reviewer'),
            self::OBSERVER => __('app.role_descriptions.observer'),
        };
    }

    public function getResponsibilities(): array
    {
        return match ($this) {
            self::ASSIGNEE => ['execute', 'update_progress', 'request_help', 'mark_complete'],
            self::APPROVER => ['approve', 'reject', 'request_changes', 'final_approval'],
            self::REVIEWER => ['review', 'comment', 'suggest', 'request_revision'],
            self::OBSERVER => ['view', 'receive_notifications', 'track_progress'],
        };
    }

    public function hasResponsibility(string $responsibility): bool
    {
        return in_array($responsibility, $this->getResponsibilities());
    }

    public function shouldReceiveNotificationFor(string $status): bool
    {
        return match ([$this, $status]) {
            [self::ASSIGNEE, 'assigned'] => true,
            [self::ASSIGNEE, 'rejected'] => true,
            [self::ASSIGNEE, 'revision_requested'] => true,
            [self::REVIEWER, 'review_requested'] => true,
            [self::REVIEWER, 'submitted_for_review'] => true,
            [self::APPROVER, 'approval_requested'] => true,
            [self::APPROVER, 'submitted_for_approval'] => true,
            [self::OBSERVER, 'completed'] => true,
            [self::OBSERVER, 'cancelled'] => true,
            default => false,
        };
    }

    public function canTriggerStatusChange(string $fromStatus, string $toStatus): bool
    {
        return match ([$this, $fromStatus, $toStatus]) {
            [self::ASSIGNEE, 'assigned', 'in_progress'] => true,
            [self::ASSIGNEE, 'in_progress', 'review_requested'] => true,
            [self::ASSIGNEE, 'revision_requested', 'in_progress'] => true,
            [self::REVIEWER, 'review_requested', 'reviewed'] => true,
            [self::REVIEWER, 'review_requested', 'revision_requested'] => true,
            [self::APPROVER, 'approval_requested', 'approved'] => true,
            [self::APPROVER, 'approval_requested', 'rejected'] => true,
            default => false,
        };
    }

    public function isActionRequired(): bool
    {
        return match ($this) {
            self::ASSIGNEE => true,
            self::APPROVER => true,
            self::REVIEWER => false,
            self::OBSERVER => false,
        };
    }

    public function canBlockCompletion(): bool
    {
        return match ($this) {
            self::ASSIGNEE => true,
            self::APPROVER => true,
            self::REVIEWER => false,
            self::OBSERVER => false,
        };
    }

    public static function getDefault(): self
    {
        return self::ASSIGNEE;
    }
}