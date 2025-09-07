<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Document;
use App\Models\Feedbacks\GeneralFeedback;
use App\Models\User;
use Hdaklue\Porter\RoleFactory;

final class GeneralFeedbackPolicy
{
    /**
     * Determine whether the user can view any feedbacks.
     */
    public function viewAny(User $user): bool
    {
        // Users can view feedbacks if they have access to the feedbackable entity
        return true;
    }

    /**
     * Determine whether the user can view the feedback.
     */
    public function view(User $user, GeneralFeedback $feedback): bool
    {
        // User can view feedback if:
        // 1. They created it
        // 2. They have access to the feedbackable entity
        // 3. They are an admin/moderator

        if ($feedback->creator_id === $user->id) {
            return true;
        }

        if ($user->hasRole(['ADMIN', 'MODERATOR'])) {
            return true;
        }

        // Check if user has access to the feedbackable entity
        return $this->canAccessFeedbackable($user, $feedback);
    }

    /**
     * Determine whether the user can create feedbacks.
     */
    public function create(User $user): bool
    {
        // All authenticated users can create feedback
        return true;
    }

    /**
     * Determine whether the user can update the feedback.
     */
    public function update(User $user, GeneralFeedback $feedback): bool
    {
        // User can update feedback if:
        // 1. They created it and it's still open
        // 2. They are an admin/moderator

        if ($user->hasRole(['ADMIN', 'MODERATOR'])) {
            return true;
        }

        return $feedback->creator_id === $user->id && $feedback->is_open;
    }

    /**
     * Determine whether the user can delete the feedback.
     */
    public function delete(User $user, GeneralFeedback $feedback): bool
    {
        // User can delete feedback if:
        // 1. They created it and it's still open
        // 2. They are an admin

        if ($user->hasRole('ADMIN')) {
            return true;
        }

        return $feedback->creator_id === $user->id && $feedback->is_open;
    }

    /**
     * Determine whether the user can restore the feedback.
     */
    public function restore(User $user, GeneralFeedback $feedback): bool
    {
        return $user->hasRole('ADMIN');
    }

    /**
     * Determine whether the user can permanently delete the feedback.
     */
    public function forceDelete(User $user, GeneralFeedback $feedback): bool
    {
        return $user->hasRole('ADMIN');
    }

    /**
     * Determine whether the user can resolve feedbacks.
     */
    public function resolve(User $user, GeneralFeedback $feedback): bool
    {
        // User can resolve feedback if:
        // 1. They are an admin/moderator
        // 2. They have contributor+ access to the feedbackable entity
        // 3. They are not the creator (can't resolve own feedback)

        if ($feedback->creator_id === $user->id) {
            return false; // Can't resolve own feedback
        }

        if ($user->hasRole(['ADMIN', 'MODERATOR'])) {
            return true;
        }

        // Check if user has contributor+ access to the feedbackable entity
        return $this->canManageFeedbackable($user, $feedback);
    }

    /**
     * Determine whether the user can reject feedbacks.
     */
    public function reject(User $user, GeneralFeedback $feedback): bool
    {
        // Same rules as resolve
        return $this->resolve($user, $feedback);
    }

    /**
     * Determine whether the user can reopen feedbacks.
     */
    public function reopen(User $user, GeneralFeedback $feedback): bool
    {
        // User can reopen feedback if:
        // 1. They are an admin/moderator
        // 2. They created the original feedback
        // 3. They have contributor+ access to the feedbackable entity

        if ($user->hasRole(['ADMIN', 'MODERATOR'])) {
            return true;
        }

        if ($feedback->creator_id === $user->id) {
            return true;
        }

        return $this->canManageFeedbackable($user, $feedback);
    }

    /**
     * Determine whether the user can create feedback on a specific feedbackable.
     */
    public function createOn(User $user, $feedbackable): bool
    {
        // For pages, user needs access to the flow
        if ($feedbackable instanceof Document) {
            return $user->canViewFlow($feedbackable->flow);
        }

        // For other entities, implement specific creation logic
        return method_exists($feedbackable, 'canReceiveFeedbackFrom')
            ? $feedbackable->canReceiveFeedbackFrom($user)
            : true;
    }

    /**
     * Determine whether the user can view feedbacks for a specific feedbackable.
     */
    public function viewForFeedbackable(User $user, $feedbackable): bool
    {
        // Same logic as createOn for now
        return $this->createOn($user, $feedbackable);
    }

    /**
     * Check if user can access the feedbackable entity.
     */
    private function canAccessFeedbackable(
        User $user,
        GeneralFeedback $feedback,
    ): bool {
        $feedbackable = $feedback->feedbackable;

        // If feedbackable doesn't exist, deny access
        if (!$feedbackable) {
            return false;
        }

        // For pages, check if user has access to the page
        if ($feedbackable instanceof Document) {
            return $user->canViewFlow($feedbackable->flow);
        }

        // For other entities, implement specific access logic
        // This is a fallback - you might want to implement more specific checks
        return method_exists($feedbackable, 'canBeViewedBy')
            ? $feedbackable->canBeViewedBy($user)
            : true;
    }

    /**
     * Check if user can manage feedback on the feedbackable entity.
     */
    private function canManageFeedbackable(
        User $user,
        GeneralFeedback $feedback,
    ): bool {
        $feedbackable = $feedback->feedbackable;

        if (!$feedbackable) {
            return false;
        }

        // For pages, check if user has contributor+ access to the flow
        if ($feedbackable instanceof Document) {
            return $user->hasRoleOnFlow(
                $feedbackable->flow,
                RoleFactory::admin(),
            );
        }

        // For other entities, implement specific management logic
        return method_exists($feedbackable, 'canBeManagedBy')
            ? $feedbackable->canBeManagedBy($user)
            : false;
    }
}
