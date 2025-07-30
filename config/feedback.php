<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Concrete Feedback Models
    |--------------------------------------------------------------------------
    |
    | This array defines all concrete feedback model classes that extend
    | the BaseFeedback abstract model. This serves as the single source of
    | truth for feedback types across the application.
    |
    */

    'concrete_models' => [
        'video' => \App\Models\VideoFeedback::class,
        'audio' => \App\Models\AudioFeedback::class,
        'document' => \App\Models\DocumentFeedback::class,
        'design' => \App\Models\DesignFeedback::class,
        'general' => \App\Models\GeneralFeedback::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Feedback Settings
    |--------------------------------------------------------------------------
    |
    | Default configuration values for feedback creation and behavior.
    |
    */

    'defaults' => [
        'status' => \App\Enums\Feedback\FeedbackStatus::OPEN,
        'urgency' => \App\Enums\Feedback\FeedbackUrgency::NORMAL,
        'auto_resolve_timeout' => 30, // days
        'notification_enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Video Feedback Settings
    |--------------------------------------------------------------------------
    |
    | Configuration specific to video feedback functionality.
    |
    */

    'video' => [
        'max_timestamp' => 7200, // 2 hours in seconds
        'min_region_duration' => 0.1, // minimum region duration in seconds
        'max_region_duration' => 3600, // maximum region duration in seconds
        'coordinate_tolerance' => 10, // pixels
        'timestamp_tolerance' => 0.1, // seconds
        'supported_formats' => ['mp4', 'webm', 'mov', 'avi'],
        'frame_extraction' => [
            'enabled' => true,
            'quality' => 80,
            'max_width' => 1920,
            'max_height' => 1080,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audio Feedback Settings
    |--------------------------------------------------------------------------
    |
    | Configuration specific to audio feedback functionality.
    |
    */

    'audio' => [
        'max_duration' => 7200, // 2 hours in seconds
        'min_duration' => 0.1, // minimum duration in seconds
        'waveform_sample_rate' => 10, // samples per second for visualization
        'amplitude_threshold_high' => 0.8,
        'amplitude_threshold_low' => 0.2,
        'supported_formats' => ['mp3', 'wav', 'ogg', 'aac', 'm4a'],
        'frequency_analysis' => [
            'enabled' => true,
            'bands' => [
                'bass' => [20, 250],      // Hz range
                'low_mid' => [250, 500],
                'mid' => [500, 2000],
                'high_mid' => [2000, 4000],
                'treble' => [4000, 20000],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Feedback Settings
    |--------------------------------------------------------------------------
    |
    | Configuration specific to document feedback functionality.
    |
    */

    'document' => [
        'supported_block_types' => [
            'paragraph', 'header', 'list', 'quote', 'code',
            'table', 'image', 'embed', 'delimiter', 'warning', 'checklist'
        ],
        'max_selection_length' => 5000, // characters
        'block_id_pattern' => '/^block_[a-f0-9-]{36}$/', // UUID pattern
        'editor_versions' => [
            'supported' => ['2.0', '2.1', '2.2'],
            'current' => '2.2',
        ],
        'text_selection' => [
            'min_length' => 1,
            'max_length' => 1000,
            'context_length' => 100, // characters before/after selection
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Design Feedback Settings
    |--------------------------------------------------------------------------
    |
    | Configuration specific to design feedback functionality.
    |
    */

    'design' => [
        'supported_annotation_types' => [
            'point', 'rectangle', 'circle', 'arrow', 'text',
            'polygon', 'area', 'line', 'freehand'
        ],
        'canvas_limits' => [
            'max_width' => 8192,
            'max_height' => 8192,
            'min_width' => 100,
            'min_height' => 100,
        ],
        'coordinate_precision' => 1, // decimal places
        'zoom_limits' => [
            'min' => 0.1,  // 10%
            'max' => 10.0, // 1000%
            'default' => 1.0, // 100%
        ],
        'annotation_defaults' => [
            'color' => 'red',
            'stroke_width' => 2,
            'font_size' => 14,
            'font_family' => 'Arial',
        ],
        'clustering' => [
            'enabled' => true,
            'radius' => 50, // pixels
            'min_annotations' => 2,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | General Feedback Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for general/fallback feedback functionality.
    |
    */

    'general' => [
        'supported_categories' => [
            'ui' => 'User Interface',
            'ux' => 'User Experience',
            'content' => 'Content',
            'functionality' => 'Functionality',
            'performance' => 'Performance',
            'accessibility' => 'Accessibility',
            'security' => 'Security',
            'bug' => 'Bug Report',
            'feature' => 'Feature Request',
            'improvement' => 'Improvement',
            'question' => 'Question',
            'other' => 'Other',
        ],
        'default_category' => 'other',
        'metadata_validation' => [
            'max_depth' => 5,
            'max_keys' => 50,
        ],
        'custom_data_limits' => [
            'max_size' => 65536, // bytes (64KB)
            'allowed_types' => ['string', 'integer', 'float', 'boolean', 'array'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Factory Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the FeedbackFactory service.
    |
    */

    'factory' => [
        'auto_detect_type' => true,
        'strict_validation' => true,
        'fallback_to_general' => true,
        'log_type_detection' => false,
        'cache_model_classes' => true,
        'type_detection_rules' => [
            // Order matters - first match wins
            'video' => [
                'required_any' => ['timestamp', 'start_time', 'end_time'],
                'optional' => ['x_coordinate', 'y_coordinate', 'region_data'],
                'indicators' => ['feedback_type' => ['frame', 'region']],
            ],
            'audio' => [
                'required_all' => ['start_time', 'end_time'],
                'forbidden' => ['x_coordinate', 'y_coordinate'],
                'indicators' => ['waveform_data', 'peak_amplitude', 'frequency_data'],
            ],
            'document' => [
                'required_all' => ['block_id'],
                'optional' => ['element_type', 'position_data', 'selection_data'],
                'patterns' => ['block_id' => '/^block_/'],
            ],
            'design' => [
                'required_all' => ['x_coordinate', 'y_coordinate'],
                'optional' => ['annotation_type', 'annotation_data', 'area_bounds'],
                'indicators' => ['color', 'zoom_level'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for migrating between feedback formats.
    |
    */

    'migration' => [
        'legacy_metadata_mapping' => [
            'video_frame' => 'video',
            'video_region' => 'video',
            'audio_region' => 'audio',
            'document_block' => 'document',
            'image_annotation' => 'design',
            'design_annotation' => 'design',
        ],
        'batch_size' => 100,
        'backup_enabled' => true,
        'rollback_enabled' => true,
        'validation_enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for feedback performance optimization.
    |
    */

    'performance' => [
        'caching' => [
            'enabled' => true,
            'ttl' => 3600, // 1 hour
            'tags' => ['feedback', 'models'],
            'driver' => null, // use default cache driver
        ],
        'indexing' => [
            'coordinate_spatial' => true,
            'time_range_compound' => true,
            'json_extracted_fields' => true,
        ],
        'query_limits' => [
            'max_results' => 1000,
            'max_coordinate_radius' => 500, // pixels
            'max_time_range' => 86400, // 24 hours in seconds
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Global validation rules for feedback data.
    |
    */

    'validation' => [
        'required_fields' => [
            'all' => ['creator_id', 'content', 'feedbackable_type', 'feedbackable_id'],
            'video' => ['feedback_type'],
            'audio' => ['start_time', 'end_time'],
            'document' => ['block_id'],
            'design' => ['x_coordinate', 'y_coordinate'],
            'general' => [],
        ],
        'content_limits' => [
            'min_length' => 1,
            'max_length' => 10000,
        ],
        'coordinate_limits' => [
            'x' => ['min' => 0, 'max' => 99999],
            'y' => ['min' => 0, 'max' => 99999],
        ],
        'time_limits' => [
            'timestamp' => ['min' => 0, 'max' => 86400],
            'duration' => ['min' => 0.1, 'max' => 86400],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable/disable specific feedback features.
    |
    */

    'features' => [
        'video_frame_extraction' => true,
        'audio_waveform_generation' => true,
        'design_annotation_clustering' => true,
        'document_text_selection' => true,
        'automatic_type_detection' => true,
        'cross_model_search' => true,
        'bulk_operations' => true,
        'export_functionality' => true,
        'notification_system' => true,
        'analytics_tracking' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for external integrations.
    |
    */

    'integrations' => [
        'editor_js' => [
            'version' => '2.2',
            'plugins' => ['paragraph', 'header', 'list', 'quote', 'code'],
            'api_endpoint' => '/api/editor-feedback',
        ],
        'media_processing' => [
            'enabled' => true,
            'queue' => 'media-processing',
            'timeout' => 300, // seconds
        ],
        'notifications' => [
            'channels' => ['database', 'mail'],
            'realtime' => true,
            'digest_frequency' => 'daily',
        ],
    ],

];