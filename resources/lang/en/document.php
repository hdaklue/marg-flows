<?php

declare(strict_types=1);

return [
    'editor' => [
        'save' => 'Save',
        'saving' => 'Saving...',
        'auto_save' => 'Auto-save',
        'auto_save_tooltip' => 'Auto-save every 30 seconds when enabled',
        'toggle_auto_save' => 'Toggle auto-save',
        'enabled' => 'enabled',
        'disabled' => 'disabled',
        'last_saved' => 'Last saved',
        'unsaved_changes' => 'Unsaved changes',
        'draft' => 'Draft',
        'saved' => 'Saved',
        'error' => 'Error saving',
        'no_changes' => 'No changes',
    ],

    'navigation' => [
        'unsaved_changes' => 'Unsaved Changes',
        'unsaved_description' => 'You have unsaved changes. What would you like to do?',
        'save_and_close' => 'Save & Close',
        'discard_and_close' => 'Discard & Close',
        'cancel' => 'Cancel',
        'save_description' => 'Your changes will be saved before navigating to the new page.',
        'discard_description' => 'Your changes will be lost permanently. This action cannot be undone.',
        'cancel_description' => 'Continue editing your document. Navigation will be cancelled.',
        'wait_for_processing' => 'Wait for Processing',
        'processing_description' => 'The editor is processing. Please wait or risk losing data.',
        'stay_on_current_page' => 'Stay on Current Page',
        'wait_here' => 'Wait Here',
        'stay_here' => 'Stay Here',
    ],

    'status' => [
        'creating' => 'Creating document...',
        'updating' => 'Updating document...',
        'deleting' => 'Deleting document...',
        'created' => 'Document created successfully',
        'updated' => 'Document updated successfully',
        'deleted' => 'Document deleted successfully',
        'error_creating' => 'Error creating document',
        'error_updating' => 'Error updating document',
        'error_deleting' => 'Error deleting document',
    ],

    'actions' => [
        'create' => 'Create Document',
        'edit' => 'Edit Document',
        'delete' => 'Delete Document',
        'view' => 'View Document',
        'duplicate' => 'Duplicate Document',
        'share' => 'Share Document',
        'export' => 'Export Document',
        'print' => 'Print Document',
    ],

    'labels' => [
        'title' => 'Title',
        'content' => 'Content',
        'author' => 'Author',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
        'status' => 'Status',
        'tags' => 'Tags',
        'category' => 'Category',
        'last_update' => 'Last Update',
    ],

    'context_menu' => [
        'page_options' => 'Page Options',
        'rename' => 'Rename',
        'open' => 'Open',
        'duplicate' => 'Duplicate',
        'delete' => 'Delete',
    ],

    // EditorJS specific translations migrated from app.php
    'tools' => [
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
];