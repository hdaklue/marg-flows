<?php

declare(strict_types=1);

return [
    'labels' => [
        'name' => 'Name',
        'username' => 'Username',
        'role' => 'Role',
        'email' => 'Email',
        'participants' => 'Participants',
        'member' => 'Member',
        'members' => 'Members',
    ],

    'actions' => [
        'add_member' => 'Add Member',
        'remove_member' => 'Remove Member',
        'change_role' => 'Change Role',
        'invite_member' => 'Invite Member',
        'invite_to_team' => 'Invite to Team',
        'manage_participants' => 'Manage Participants',
    ],

    'messages' => [
        'member_added' => 'Member added successfully',
        'member_removed' => 'Member removed successfully',
        'role_changed' => 'Role changed successfully',
        'invitation_sent' => 'Invitation sent successfully',
        'operation_failed' => 'Operation failed. Please try again.',
        'no_members' => 'No members found',
        'cannot_remove_yourself' => 'You cannot remove yourself',
        'cannot_change_own_role' => 'You cannot change your own role',
    ],

    'roles' => [
        'owner' => 'Owner',
        'admin' => 'Admin',
        'member' => 'Member',
        'viewer' => 'Viewer',
        'editor' => 'Editor',
        'reviewer' => 'Reviewer',
        'approver' => 'Approver',
        'observer' => 'Observer',
        'assignee' => 'Assignee',
    ],

    'tooltips' => [
        'add_member' => 'Add a new member to this item',
        'remove_member' => 'Remove this member',
        'change_role' => 'Change member role',
        'invite_member' => 'Send invitation to new member',
    ],

    'placeholders' => [
        'search_members' => 'Search members...',
        'enter_email' => 'Enter email address to invite',
        'select_role' => 'Select a role',
        'select_member' => 'Select a member',
    ],

    'confirmations' => [
        'remove_member' => 'Are you sure you want to remove this member?',
        'change_role' => 'Are you sure you want to change this member\'s role?',
    ],
];
