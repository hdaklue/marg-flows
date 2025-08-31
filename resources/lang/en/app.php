<?php

declare(strict_types=1);

// DEPRECATED: This file contains legacy translations that have been migrated to feature-specific files.
// Please use the new feature-based language files for better organization:
//
// - auth.php - Authentication related translations
// - document.php - Document editor and management (including EditorJS)
// - flow.php - Flow management and workflows
// - feedback.php - Feedback system
// - roles.php - Role and member management
// - common.php - Shared translations across features
// - ui.php - UI components and interface elements
//
// For new translations, use the appropriate feature-specific file.
// This file is kept for backward compatibility.

return [
    // Legacy support - gradually migrate these to feature-specific files
    // Most of these translations have been moved to common.php, document.php, flow.php, etc.
    
    // Navigation - use common.navigation instead
    'dashboard' => 'Dashboard',
    'flows' => 'Flows',
    'documents' => 'Documents',
    'users' => 'Users',
    'tenants' => 'Tenants',
    'settings' => 'Settings',
    'profile' => 'Profile',
    
    // Actions - use common.actions instead
    'create' => 'Create',
    'edit' => 'Edit',
    'delete' => 'Delete',
    'save' => 'Save',
    'cancel' => 'Cancel',
    'submit' => 'Submit',
    'search' => 'Search',
    'filter' => 'Filter',
    'view' => 'View',
    'download' => 'Download',
    'upload' => 'Upload',
    'invite' => 'Invite',
    'assign' => 'Assign',
    'approve' => 'Approve',
    'reject' => 'Reject',
    'publish' => 'Publish',
    'draft' => 'Draft',
    
    // Labels - use common.labels instead
    'name' => 'Name',
    'email' => 'Email',
    'password' => 'Password',
    'title' => 'Title',
    'description' => 'Description',
    'status' => 'Status',
    'date' => 'Date',
    'created_at' => 'Created At',
    'updated_at' => 'Updated At',
    'actions' => 'Actions',
    'role' => 'Role',
    'permissions' => 'Permissions',
    'account_type' => 'Account Type',
    'last_login' => 'Last Login',
    'invited_by' => 'Invited By',
    
    // Status - use common.status instead
    'active' => 'Active',
    'inactive' => 'Inactive',
    'pending' => 'Pending',
    'approved' => 'Approved',
    'rejected' => 'Rejected',
    'completed' => 'Completed',
    'in_progress' => 'In Progress',
    'draft' => 'Draft',
    'published' => 'Published',
    
    // Messages - use common.messages instead
    'success' => 'Success',
    'error' => 'Error',
    'warning' => 'Warning',
    'info' => 'Info',
    'created_successfully' => 'Created successfully',
    'updated_successfully' => 'Updated successfully',
    'deleted_successfully' => 'Deleted successfully',
    'operation_completed' => 'Operation completed successfully',
    'no_records_found' => 'No records found',
    'confirm_delete' => 'Are you sure you want to delete this item?',
    
    // Form placeholders - use common.placeholders instead
    'enter_name' => 'Enter name',
    'enter_email' => 'Enter email address',
    'enter_title' => 'Enter title',
    'enter_description' => 'Enter description',
    'search_placeholder' => 'Search...',
    'select_option' => 'Select an option',
    
    // Flow stages - use flow.stages instead
    'flow_stages' => [
        'draft' => 'Draft',
        'active' => 'Active',
        'paused' => 'Paused',
        'blocked' => 'Blocked',
        'completed' => 'Completed',
        'canceled' => 'Canceled',
        'review' => 'Review',
        'approval' => 'Approval',
        'published' => 'Published',
        'archived' => 'Archived',
    ],
    
    // Roles - use roles.types instead
    'roles' => [
        'assignee' => 'Assignee',
        'approver' => 'Approver',
        'reviewer' => 'Reviewer', 
        'observer' => 'Observer',
    ],
    
    // File upload - use ui.components.file_upload instead
    'file_upload' => [
        'drag_drop' => 'Drag and drop files here or click to browse',
        'max_size' => 'Maximum file size: :size',
        'supported_formats' => 'Supported formats: :formats',
        'uploading' => 'Uploading...',
        'upload_complete' => 'Uploaded Files',
        'upload_failed' => 'Upload failed',
        'video_upload' => 'Video Upload',
        'video_file' => 'Video File',
    ],
    
    // Account types - use roles.account_types instead
    'account_types' => [
        'admin' => 'Admin',
        'manager' => 'Manager',
        'user' => 'User',
    ],
    
    // Role descriptions - use roles.descriptions instead
    'role_descriptions' => [
        'assignee' => 'Responsible for completing the task',
        'approver' => 'Reviews and approves task completion',
        'reviewer' => 'Provides feedback and suggestions',
        'observer' => 'Monitors progress without direct involvement',
    ],
    
    // Feedback status - use feedback.status instead
    'feedback_status' => [
        'open' => 'Open',
        'running' => 'Running',
        'resolved' => 'Resolved',
        'rejected' => 'Rejected',
    ],
    
    // Feedback urgency - use feedback.urgency instead
    'feedback_urgency' => [
        'normal' => 'Normal',
        'suggestion' => 'Suggestion',
        'urgent' => 'Urgent',
    ],
    
    // System roles - use roles.system_roles instead
    'system_roles' => [
        'admin' => 'Administrator',
        'manager' => 'Manager',
        'editor' => 'Editor',
        'contributor' => 'Contributor',
        'viewer' => 'Viewer',
        'guest' => 'Guest',
    ],
    
    // System role descriptions - use roles.system_descriptions instead
    'system_role_descriptions' => [
        'admin' => 'Full administrative access to assigned entities',
        'manager' => 'Manage content and assign roles within entities',
        'editor' => 'Create, edit, and delete content',
        'contributor' => 'Create and edit own content',
        'viewer' => 'Read-only access to content',
        'guest' => 'Limited access to public content',
    ],
    
    // EditorJS tool titles - use document.tools instead
    'editor_tools' => [
        'paragraph' => 'Text',
        'header' => 'Heading',
        'images' => 'Image',
        'table' => 'Table',
        'nestedList' => 'List',
        'alert' => 'Alert',
        'linkTool' => 'Link',
        'videoEmbed' => 'Video Embed',
        'videoUpload' => 'Video Upload',
        'commentTune' => 'Add Comment',
    ],
    
    // EditorJS interface translations - use document.ui, document.toolNames, document.blockTunes instead
    'editor_ui' => [
        'ui' => [
            'blockTunes' => [
                'toggler' => [
                    'Click to tune' => 'Click to tune',
                    'or drag to move' => 'or drag to move',
                ],
            ],
            'inlineToolbar' => [
                'converter' => [
                    'Convert to' => 'Convert to',
                ],
            ],
            'toolbar' => [
                'toolbox' => [
                    'Add' => 'Add',
                    'Filter' => 'Filter',
                    'Nothing found' => 'Nothing found',
                ],
            ],
            'popover' => [
                'Filter' => 'Filter',
                'Nothing found' => 'Nothing found',
            ],
        ],
        'toolNames' => [
            'Text' => 'Text',
            'Heading' => 'Heading',
            'List' => 'List',
            'Table' => 'Table',
            'Link' => 'Link',
            'Bold' => 'Bold',
            'Italic' => 'Italic',
        ],
        'blockTunes' => [
            'delete' => [
                'Delete' => 'Delete',
            ],
            'moveUp' => [
                'Move up' => 'Move up',
            ],
            'moveDown' => [
                'Move down' => 'Move down',
            ],
            'commentTune' => [
                'Add Comment' => 'Add Comment',
                'Comment' => 'Comment',
                'Add a comment' => 'Add a comment',
            ],
        ],
    ],
];