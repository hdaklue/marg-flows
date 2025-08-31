<?php

declare(strict_types=1);

return [
    'actions' => [
        'create' => 'Create Flow',
        'edit' => 'Edit Flow',
        'delete' => 'Delete Flow',
        'view' => 'View Flow',
        'duplicate' => 'Duplicate Flow',
        'archive' => 'Archive Flow',
        'restore' => 'Restore Flow',
        'publish' => 'Publish Flow',
        'unpublish' => 'Unpublish Flow',
    ],

    'labels' => [
        'name' => 'Flow Name',
        'description' => 'Description',
        'status' => 'Status',
        'stage' => 'Stage',
        'priority' => 'Priority',
        'assigned_to' => 'Assigned To',
        'due_date' => 'Due Date',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
        'documents' => 'Documents',
        'participants' => 'Participants',
    ],

    // Migrated from app.php flow_stages
    'stages' => [
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
        'in_progress' => 'In Progress',
    ],

    'priority' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
    ],

    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'archived' => 'Archived',
        'published' => 'Published',
        'unpublished' => 'Unpublished',
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ],

    'messages' => [
        'created' => 'Flow created successfully',
        'updated' => 'Flow updated successfully',
        'deleted' => 'Flow deleted successfully',
        'archived' => 'Flow archived successfully',
        'restored' => 'Flow restored successfully',
        'published' => 'Flow published successfully',
        'unpublished' => 'Flow unpublished successfully',
        'error_creating' => 'Error creating flow',
        'error_updating' => 'Error updating flow',
        'error_deleting' => 'Error deleting flow',
    ],

    'documents' => [
        'title' => 'Flow Documents',
        'add_document' => 'Add Document',
        'no_documents' => 'No documents found for this flow',
        'document_count' => '{0} No documents|{1} 1 document|[2,*] :count documents',
    ],

    'tabs' => [
        'active' => 'Active',
        'draft' => 'Draft',
        'all' => 'All',
    ],

    'table' => [
        'columns' => [
            'title' => 'Title',
            'stage' => 'Stage',
            'creator_avatar' => 'Creator',
            'participant_stack' => 'Members',
        ],
        'actions' => [
            'view' => 'Documents',
        ],
    ],
];