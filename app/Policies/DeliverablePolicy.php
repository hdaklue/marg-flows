<?php

declare(strict_types=1);

namespace App\Policies;

use App\Contracts\Role\AssignableEntity;
use App\Enums\AssigneeRole;
use App\Enums\Role\RoleEnum;
use App\Models\Deliverable;
use App\Models\User;

final class DeliverablePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return filamentTenant()->isParticipant($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(AssignableEntity $user, Deliverable $deliverable): bool
    {
        return $deliverable->isParticipant($user) || 
               $deliverable->flow->isParticipant($user) ||
               $deliverable->getTenant()->isAdmin($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAssignmentOn(filamentTenant(), RoleEnum::ADMIN) ||
               $user->hasAssignmentOn(filamentTenant(), RoleEnum::MANAGER);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Deliverable $deliverable): bool
    {
        // Admin/Manager on deliverable or flow can always update
        if ($user->hasAssignmentOn($deliverable, RoleEnum::ADMIN) || 
            $user->hasAssignmentOn($deliverable, RoleEnum::MANAGER) ||
            $user->hasAssignmentOn($deliverable->flow, RoleEnum::ADMIN) || 
            $user->hasAssignmentOn($deliverable->flow, RoleEnum::MANAGER)) {
            return true;
        }

        // Assignees can update deliverables assigned to them
        return $deliverable->hasParticipantWithRole($user, AssigneeRole::ASSIGNEE);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Deliverable $deliverable): bool
    {
        return $user->hasAssignmentOn($deliverable, RoleEnum::ADMIN) ||
               $user->hasAssignmentOn($deliverable->flow, RoleEnum::ADMIN);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Deliverable $deliverable): bool
    {
        return $user->hasAssignmentOn($deliverable, RoleEnum::ADMIN) ||
               $user->hasAssignmentOn($deliverable->flow, RoleEnum::ADMIN);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Deliverable $deliverable): bool
    {
        return $user->hasAssignmentOn(filamentTenant(), RoleEnum::ADMIN);
    }

    /**
     * Determine whether the user can manage participants (assign/remove people).
     */
    public function manageParticipants(User $user, Deliverable $deliverable): bool
    {
        return $user->hasAssignmentOn($deliverable, RoleEnum::ADMIN) ||
               $user->hasAssignmentOn($deliverable, RoleEnum::MANAGER) ||
               $user->hasAssignmentOn($deliverable->flow, RoleEnum::ADMIN) ||
               $user->hasAssignmentOn($deliverable->flow, RoleEnum::MANAGER);
    }

    /**
     * Determine whether the user can submit versions of the deliverable.
     */
    public function submitVersion(User $user, Deliverable $deliverable): bool
    {
        // Assignees can submit versions
        return $deliverable->hasParticipantWithRole($user, AssigneeRole::ASSIGNEE) ||
               $this->manageParticipants($user, $deliverable);
    }

    /**
     * Determine whether the user can review deliverable submissions.
     */
    public function review(User $user, Deliverable $deliverable): bool
    {
        // Reviewers, approvers, and managers can review
        return $deliverable->hasParticipantWithRole($user, AssigneeRole::REVIEWER) ||
               $deliverable->hasParticipantWithRole($user, AssigneeRole::APPROVER) ||
               $this->manageParticipants($user, $deliverable);
    }

    /**
     * Determine whether the user can approve deliverable completion.
     */
    public function approve(User $user, Deliverable $deliverable): bool
    {
        // Approvers and managers can approve
        return $deliverable->hasParticipantWithRole($user, AssigneeRole::APPROVER) ||
               $this->manageParticipants($user, $deliverable);
    }

    /**
     * Determine whether the user can mark deliverable as completed.
     */
    public function markComplete(User $user, Deliverable $deliverable): bool
    {
        // Assignees can mark as complete, or managers/admins
        return $deliverable->hasParticipantWithRole($user, AssigneeRole::ASSIGNEE) ||
               $this->manageParticipants($user, $deliverable);
    }

    /**
     * Determine whether the user can change deliverable status.
     */
    public function updateStatus(User $user, Deliverable $deliverable): bool
    {
        // Check role-based status change permissions
        $userRoles = $deliverable->getParticipantRoles($user);
        
        foreach ($userRoles as $role) {
            if ($role->isActionRequired()) {
                return true;
            }
        }

        return $this->manageParticipants($user, $deliverable);
    }

    /**
     * Determine whether the user can change deliverable priority.
     */
    public function updatePriority(User $user, Deliverable $deliverable): bool
    {
        return $this->manageParticipants($user, $deliverable);
    }

    /**
     * Determine whether the user can change deliverable dates.
     */
    public function updateDates(User $user, Deliverable $deliverable): bool
    {
        return $this->manageParticipants($user, $deliverable);
    }

    /**
     * Determine whether the user can view deliverable specifications.
     */
    public function viewSpecifications(User $user, Deliverable $deliverable): bool
    {
        // All participants can view specifications
        return $this->view($user, $deliverable);
    }

    /**
     * Determine whether the user can comment on the deliverable.
     */
    public function comment(User $user, Deliverable $deliverable): bool
    {
        // All participants can comment
        return $this->view($user, $deliverable);
    }

    /**
     * Determine whether the user can attach files to the deliverable.
     */
    public function attachFiles(User $user, Deliverable $deliverable): bool
    {
        // Assignees and managers can attach files
        return $deliverable->hasParticipantWithRole($user, AssigneeRole::ASSIGNEE) ||
               $this->manageParticipants($user, $deliverable);
    }

    /**
     * Determine whether the user can download deliverable files.
     */
    public function downloadFiles(User $user, Deliverable $deliverable): bool
    {
        // All participants can download files
        return $this->view($user, $deliverable);
    }
}