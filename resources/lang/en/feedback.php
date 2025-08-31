<?php

declare(strict_types=1);

return [
    'actions' => [
        'create' => 'Create Feedback',
        'submit' => 'Submit Feedback',
        'edit' => 'Edit Feedback',
        'delete' => 'Delete Feedback',
        'view' => 'View Feedback',
        'respond' => 'Respond to Feedback',
        'resolve' => 'Resolve Feedback',
        'reopen' => 'Reopen Feedback',
    ],

    'labels' => [
        'title' => 'Title',
        'description' => 'Description',
        'category' => 'Category',
        'urgency' => 'Urgency',
        'status' => 'Status',
        'submitted_by' => 'Submitted By',
        'assigned_to' => 'Assigned To',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
        'resolved_at' => 'Resolved At',
    ],

    // Migrated from app.php feedback_urgency
    'urgency' => [
        'normal' => 'Normal',
        'suggestion' => 'Suggestion',
        'urgent' => 'Urgent',
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'critical' => 'Critical',
    ],

    // Migrated from app.php feedback_status
    'status' => [
        'open' => 'Open',
        'running' => 'Running',
        'resolved' => 'Resolved',
        'rejected' => 'Rejected',
        'in_progress' => 'In Progress',
        'closed' => 'Closed',
    ],

    'categories' => [
        'bug' => 'Bug Report',
        'feature' => 'Feature Request',
        'improvement' => 'Improvement',
        'question' => 'Question',
        'other' => 'Other',
    ],

    'messages' => [
        'created' => 'Feedback submitted successfully',
        'updated' => 'Feedback updated successfully',
        'deleted' => 'Feedback deleted successfully',
        'resolved' => 'Feedback resolved successfully',
        'reopened' => 'Feedback reopened successfully',
        'error_creating' => 'Error submitting feedback',
        'error_updating' => 'Error updating feedback',
        'error_deleting' => 'Error deleting feedback',
    ],

    'form' => [
        'title_placeholder' => 'Enter a brief title for your feedback',
        'description_placeholder' => 'Provide detailed information about your feedback',
        'select_category' => 'Select a category',
        'select_urgency' => 'Select urgency level',
    ],

    'modal' => [
        'title' => 'Submit Feedback',
        'description' => 'Help us improve by sharing your feedback',
        'cancel' => 'Cancel',
        'submit' => 'Submit Feedback',
    ],
];