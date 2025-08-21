<?php

declare(strict_types=1);

return [
    'actions' => [
        'manage_members' => 'Manage Members',
        'add_member' => 'Add Member',
        'remove_member' => 'Remove Member',
        'change_role' => 'Change Role',
        'invite_member' => 'Invite Member',
        'cancel_invitation' => 'Cancel Invitation',
        'resend_invitation' => 'Resend Invitation',
        'assign' => 'Assign',
        'approve' => 'Approve',
        'reject' => 'Reject',
        'invite' => 'Invite',
    ],

    'labels' => [
        'role' => 'Role',
        'member' => 'Member',
        'members' => 'Members',
        'permissions' => 'Permissions',
        'email' => 'Email',
        'name' => 'Name',
        'status' => 'Status',
        'joined_at' => 'Joined At',
        'invited_at' => 'Invited At',
        'invited_by' => 'Invited By',
        'last_login' => 'Last Login',
        'account_type' => 'Account Type',
    ],

    // Migrated from app.php roles
    'types' => [
        'assignee' => 'Assignee',
        'approver' => 'Approver',
        'reviewer' => 'Reviewer',
        'observer' => 'Observer',
    ],

    // Migrated from app.php system_roles
    'system_roles' => [
        'admin' => 'Administrator',
        'manager' => 'Manager',
        'editor' => 'Editor',
        'contributor' => 'Contributor',
        'viewer' => 'Viewer',
        'guest' => 'Guest',
        'user' => 'User',
    ],

    // Migrated from app.php account_types
    'account_types' => [
        'admin' => 'Admin',
        'manager' => 'Manager',
        'user' => 'User',
    ],

    'permissions' => [
        'can_manage_members' => 'Can manage members',
        'can_edit' => 'Can edit',
        'can_view' => 'Can view',
        'can_delete' => 'Can delete',
        'can_approve' => 'Can approve',
        'can_review' => 'Can review',
        'can_comment' => 'Can comment',
        'can_export' => 'Can export',
    ],

    'status' => [
        'active' => 'Active',
        'pending' => 'Pending',
        'inactive' => 'Inactive',
        'invited' => 'Invited',
        'expired' => 'Expired',
    ],

    'messages' => [
        'member_added' => 'Member added successfully',
        'member_removed' => 'Member removed successfully',
        'role_changed' => 'Member role updated successfully',
        'invitation_sent' => 'Invitation sent successfully',
        'invitation_cancelled' => 'Invitation cancelled successfully',
        'invitation_resent' => 'Invitation resent successfully',
        'error_adding_member' => 'Error adding member',
        'error_removing_member' => 'Error removing member',
        'error_changing_role' => 'Error changing member role',
        'error_sending_invitation' => 'Error sending invitation',
    ],

    'modal' => [
        'manage_members_title' => 'Manage Members',
        'add_member_title' => 'Add Member',
        'remove_member_title' => 'Remove Member',
        'change_role_title' => 'Change Role',
        'confirm_remove' => 'Are you sure you want to remove this member?',
        'confirm_role_change' => 'Are you sure you want to change this member\'s role?',
        'select_role' => 'Select a role',
        'enter_email' => 'Enter member email',
        'cancel' => 'Cancel',
        'save' => 'Save',
        'remove' => 'Remove',
        'add' => 'Add',
    ],

    // Migrated from app.php role_descriptions
    'descriptions' => [
        'assignee' => 'Responsible for completing the task',
        'approver' => 'Reviews and approves task completion',
        'reviewer' => 'Provides feedback and suggestions',
        'observer' => 'Monitors progress without direct involvement',
    ],

    // Migrated from app.php system_role_descriptions
    'system_descriptions' => [
        'admin' => 'Full administrative access to assigned entities',
        'manager' => 'Manage content and assign roles within entities',
        'editor' => 'Create, edit, and delete content',
        'contributor' => 'Create and edit own content',
        'viewer' => 'Read-only access to content',
        'guest' => 'Limited access to public content',
    ],

    'tooltips' => [
        'owner_tooltip' => 'Full access to all features and settings',
        'admin_tooltip' => 'Can manage members and most settings',
        'manager_tooltip' => 'Can manage content and assign tasks',
        'member_tooltip' => 'Can create and edit content',
        'viewer_tooltip' => 'Can only view content',
    ],
];