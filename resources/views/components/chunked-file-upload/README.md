# Enhanced ChunkedFileUpload Component

Modern Alpine.js component for file uploads with chunked upload support, modal interface, and enhanced UX.

## Features

- **Modal and inline modes** with enhanced UI
- **Enhanced drag & drop** with visual feedback and mobile support
- **Real-time progress tracking** with ETA and speed indicators
- **URL import functionality** for importing files from external sources
- **File management** (add, remove, cancel, retry uploads)
- **Enhanced error handling** with detailed error messages
- **Responsive design** with mobile and tablet support
- **Dark mode support** using zinc color scheme
- **Accessibility features** (ARIA, keyboard navigation, screen reader support)
- **Event-driven architecture** with proper Alpine.js patterns

## Architecture

Following Laravel/Filament v4 and Alpine.js v3 conventions:

```
resources/
├── js/components/ChunkedFileUpload/
│   └── index.js                    # Main Alpine.js component
├── views/components/ChunkedFileUpload/
│   ├── index.blade.php            # Main component view
│   ├── UploadArea.blade.php       # Drag & drop upload area
│   ├── FileList.blade.php         # Completed files display
│   ├── ProgressSection.blade.php  # Upload progress display
│   ├── UrlImport.blade.php        # URL import functionality
│   └── Messages.blade.php         # Error/success messaging
```

## Usage Examples

### Basic Usage (Inline Mode)

```php
ChunkedFileUpload::make('files')
    ->image()
    ->maxFiles(5)
    ->maxSize('50MB')
```

### Modal Mode with URL Import

```php
ChunkedFileUpload::make('documents')
    ->modal()
    ->urlImport()
    ->acceptedFileTypes(['application/pdf', 'image/*'])
    ->maxSize('100MB')
    ->previewable()
```

### Video Upload with Custom Configuration

```php
ChunkedFileUpload::make('videos')
    ->video()
    ->modal()
    ->chunkSize(10 * 1024 * 1024) // 10MB chunks
    ->maxParallelUploads(2)
    ->maxSize('500MB')
    ->previewable()
```

### Advanced Configuration

```php
ChunkedFileUpload::make('media')
    ->modal()
    ->urlImport()
    ->autoFocus(false)
    ->acceptedFileTypes([
        'image/jpeg', 'image/png', 'image/gif',
        'video/mp4', 'video/webm',
        'application/pdf'
    ])
    ->maxFiles(10)
    ->maxSize('250MB')
    ->chunkSize(5 * 1024 * 1024)
    ->maxParallelUploads(3)
    ->previewable()
    ->alignment(Alignment::Center)
```

## Component Methods

### Configuration Methods

- `modal(bool $modal = true)` - Enable modal mode
- `urlImport(bool $urlImport = true)` - Enable URL import functionality
- `autoFocus(bool $autoFocus = true)` - Auto-focus first input in modal
- `image(bool $image = true)` - Configure for image uploads
- `video(bool $video = true)` - Configure for video uploads
- `previewable(bool $previewable = true)` - Enable file previews
- `chunkSize(int $bytes)` - Set chunk size in bytes
- `maxParallelUploads(int $count)` - Max simultaneous uploads
- `alignment(string $alignment)` - Component alignment

### Configuration Getters

- `isModalMode(): bool` - Check if modal mode is enabled
- `hasUrlImport(): bool` - Check if URL import is enabled
- `getAutoFocus(): bool` - Get auto-focus setting
- `isPreviewable(): bool` - Check if previews are enabled
- `isImageUpload(): bool` - Check if configured for images
- `isVideoUpload(): bool` - Check if configured for videos

## Alpine.js Events

The component emits several events for integration:

```javascript
// Modal events
@modal-opened="console.log('Modal opened')"
@modal-closed="console.log('Modal closed')"

// Upload events  
@file-uploaded="console.log('File uploaded:', $event.detail)"
@file-error="console.log('Upload error:', $event.detail)"
@file-cancelled="console.log('Upload cancelled:', $event.detail)"
```

## Accessibility Features

- **Keyboard Navigation**: Full keyboard support with proper focus management
- **Screen Reader Support**: ARIA labels, live regions for announcements
- **Mobile Support**: Touch-friendly with responsive design
- **Focus Management**: Proper focus trapping in modal mode

## Customization

### Color Scheme

Uses Zinc/Sky color scheme following project conventions:
- Primary: Sky (blue)
- Gray: Zinc  
- Success: Emerald
- Warning: Amber
- Danger: Red

### Dark Mode

Fully supports dark mode with proper contrast ratios and color adjustments.

### Mobile Responsiveness

- Adaptive layouts for different screen sizes
- Touch-friendly interface elements  
- Responsive modal sizing
- Grid layouts adjust for mobile/tablet/desktop

## Browser Support

- Modern browsers with ES2015+ support
- Safari 12+, Chrome 70+, Firefox 65+, Edge 79+
- Mobile Safari, Chrome Mobile
- Proper graceful degradation for older browsers

## Performance Considerations

- **Chunked Uploads**: Large files split into manageable chunks
- **Concurrent Control**: Limits parallel uploads to prevent overwhelming
- **Memory Management**: Proper cleanup of event listeners and abort controllers
- **Progress Tracking**: Efficient progress updates without excessive DOM manipulation

## Security Features

- **File Type Validation**: Both client and server-side validation
- **Size Limits**: Configurable file size restrictions
- **CSRF Protection**: Built-in CSRF token handling
- **Sanitization**: Safe filename handling and display