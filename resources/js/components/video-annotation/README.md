# Video Annotation Component

A comprehensive video annotation system built with Alpine.js and VideoJS, featuring frame-precise commenting, region management, and advanced playback controls.

## Features

- **Frame-Precise Annotations** - Comment and region creation aligned to video frames
- **Interactive Progress Bar** - Clickable timeline with comment dots and region indicators
- **Region Management** - Create, edit, and manage time-based video regions
- **Touch Interface** - Mobile-optimized gestures and controls
- **Context Menu** - Right-click menu for quick annotation creation
- **ViewOnly Mode** - Disable creation features while preserving viewing functionality
- **Multi-Resolution Support** - Dynamic quality switching with VideoJS
- **Dark/Light Theme** - Automatic theme adaptation

## Installation

1. Add to your build configuration in `build.js`:

```javascript
compile({
    ...defaultOptions,
    entryPoints: ['./resources/js/components/video-annotation/index.js'],
    outfile: './resources/js/dist/components/video-annotation.js',
})
```

2. Build the component:

```bash
node build.js
```

3. Include in your Blade template:

```html
<script src="{{ asset('js/dist/components/video-annotation.js') }}"></script>
```

## Basic Usage

### Livewire Component Setup

```php
// app/Livewire/VideoPlayer.php
class VideoPlayer extends Component
{
    public $config = [];
    public $comments = [];
    public $regions = [];
    public $qualitySources = [];

    public function mount()
    {
        $this->config = [
            'video' => [
                'frameRate' => 30.0,
            ],
            'mode' => [
                'viewOnly' => false, // Set to true to disable creation
            ],
            'features' => [
                'enableAnnotations' => true,
                'enableComments' => true,
                'enableProgressBarAnnotations' => true,
            ],
            // ... more config options
        ];

        // Sample video sources
        $this->qualitySources = [
            [
                'src' => 'https://example.com/video-1080p.mp4',
                'label' => '1080p',
                'quality' => '1080',
                'selected' => true,
            ],
            // ... more quality options
        ];
    }
}
```

### Blade Template Implementation

```html
<!-- resources/views/livewire/video-player.blade.php -->
<div x-data="videoAnnotation(@js($config), @js($comments), @js($regions))"
     data-quality-sources="@js($qualitySources)"
     class="w-full h-full">
    
    <!-- Video Player Component -->
    <x-video-annotation.components.player :qualitySources="$qualitySources" />
    
    <!-- Controls and Progress Bar -->
    <x-video-annotation.components.tool-bar />
    
</div>
```

## Configuration Options

### Video Settings

```php
'video' => [
    'frameRate' => 30.0, // Video frame rate for precise calculations
],
```

### Mode Configuration

```php
'mode' => [
    'viewOnly' => false, // Disable comment/region creation when true
],
```

### Feature Toggles

```php
'features' => [
    'enableAnnotations' => true,
    'enableComments' => true,
    'enableProgressBarAnnotations' => true,
    'enableVideoAnnotations' => true,
    'enableResolutionSelector' => true,
    'enableVolumeControls' => true,
    'enableFullscreenButton' => true,
    'enableSettingsMenu' => true,
],
```

### UI Customization

```php
'ui' => [
    'progressBarMode' => 'always', // 'always', 'hover', 'never'
    'showControls' => true,
    'helpTooltipLimit' => 3,
    'theme' => 'auto', // 'auto', 'light', 'dark'
],
```

### Annotation Settings

```php
'annotations' => [
    'showCommentsOnProgressBar' => true,
    'enableProgressBarComments' => true,
    'enableVideoComments' => true,
    'enableContextMenu' => true,
    'enableHapticFeedback' => true,
],
```

## Data Structures

### Comment Object

```php
[
    'commentId' => 1,
    'avatar' => 'https://example.com/avatar.jpg',
    'name' => 'John Doe',
    'body' => 'Comment text here',
    'timestamp' => 15.5, // Seconds
    'frameNumber' => 465, // Frame number at 30fps
    'frameRate' => 30.0,
]
```

### Region Object

```php
[
    'id' => 'region-1',
    'startTime' => 10.5,
    'endTime' => 25.3,
    'startFrame' => 315,
    'endFrame' => 759,
    'title' => 'Scene Title',
    'description' => 'Scene description',
    'color' => '#6366f1', // Hex color
    'opacity' => 0.6,
    'temporary' => false,
]
```

## Events

The component dispatches several events for integration:

### Livewire Events

```php
// Listen in your Livewire component
protected $listeners = [
    'commentsUpdated' => 'handleCommentsUpdate',
    'commentLoaded' => 'handleCommentLoad',
    'regionCreated' => 'handleRegionCreate',
    'regionDeleted' => 'handleRegionDelete',
];

public function handleCommentsUpdate($comments)
{
    $this->comments = $comments;
}
```

### JavaScript Events

```javascript
// Listen for video events
document.addEventListener('video-time-update', (event) => {
    console.log('Current time:', event.detail.currentTime);
});

document.addEventListener('video-duration-changed', (event) => {
    console.log('Video duration:', event.detail.duration);
});

document.addEventListener('comment-clicked', (event) => {
    console.log('Comment clicked:', event.detail.comment);
});
```

## API Methods

### Comment System

```javascript
// Add a new comment
this.addComment({
    body: 'New comment',
    timestamp: this.currentTime,
    frameNumber: this.currentFrameNumber
});

// Load specific comment
this.loadComment(commentId);

// Remove comment
this.removeComment(commentId);
```

### Region Management

```javascript
// Start region creation
this.startRegionCreationAtCurrentFrame();

// Create region programmatically
this.createRegion({
    startTime: 10.0,
    endTime: 20.0,
    title: 'New Region',
    description: 'Region description'
});

// Delete region
this.deleteRegion(regionId);
```

### Video Control

```javascript
// Seek to specific time
this.seekTo(timeInSeconds);

// Play/pause toggle
this.togglePlayPause();

// Frame navigation
this.seekToNextFrame();
this.seekToPreviousFrame();

// Volume control
this.setVolume(0.8); // 0.0 to 1.0
this.toggleMute();
```

## Keyboard Shortcuts

- **Spacebar** - Play/Pause
- **Left/Right Arrows** - Frame-by-frame navigation
- **Up/Down Arrows** - Volume control
- **M** - Mute/Unmute
- **F** - Fullscreen toggle
- **C** - Add comment at current time
- **R** - Start region creation

## Mobile Touch Gestures

- **Single Tap** - Play/Pause
- **Double Tap** - Fullscreen toggle
- **Long Press** - Context menu
- **Pinch** - Volume control
- **Swipe Left/Right** - Frame navigation
- **Swipe Up/Down** - Region creation mode

## Performance Considerations

- **Lazy Loading** - Heavy video processing deferred until initialization
- **Frame Alignment** - Comments and regions snap to video frames
- **Efficient Rendering** - Only visible elements are rendered
- **Memory Management** - Automatic cleanup of unused resources
- **Touch Optimization** - Debounced touch events for smooth interaction

## Browser Support

- **Modern Browsers** - Chrome 80+, Firefox 74+, Safari 13+, Edge 80+
- **Mobile** - iOS Safari 13+, Chrome Mobile 80+
- **Features** - ES2020, VideoJS 8+, Alpine.js 3+

## Troubleshooting

### Common Issues

1. **Videos not loading**
   - Check CORS headers on video URLs
   - Ensure video formats are supported
   - Verify quality sources array format

2. **Comments/Regions not appearing**
   - Check `videoLoaded && duration > 0` conditions
   - Verify event listener setup
   - Enable debug logging in browser console

3. **Frame alignment issues**
   - Ensure `frameRate` is set correctly in config
   - Check video metadata for actual frame rate
   - Use `CommentTime` value object for calculations

### Debug Mode

**Enable Debug Mode:**
```javascript
// In browser console
localStorage.setItem('videoAnnotationDebug', 'true');
```

**Disable Debug Mode:**
```javascript
// In browser console
localStorage.removeItem('videoAnnotationDebug');
// OR
localStorage.setItem('videoAnnotationDebug', 'false');
```

**Debug Logging Includes:**
- Video initialization and player ready states
- Region management and creation
- Comment system interactions
- Event dispatching and state changes
- Frame calculations and seeking
- Menu interactions and UI updates
- Touch interface gestures
- Performance timing information

**Note:** Debug logging is **disabled by default** to prevent console spam in production. Only logs when explicitly enabled via localStorage.

## Architecture

The component uses a modular ES6 architecture:

- **VideoPlayerCore** - VideoJS integration and playback control
- **SharedState** - Centralized state management
- **CommentSystemModule** - Comment creation and management
- **RegionManagementModule** - Region lifecycle management
- **ProgressBarModule** - Interactive timeline functionality
- **TouchInterfaceModule** - Mobile gesture handling
- **EventHandlerModule** - Event coordination
- **ContextDisplayModule** - Tooltip and context display

Each module is focused on a specific concern, making the system maintainable and testable.