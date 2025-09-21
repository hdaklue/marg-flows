# DocumentVersionTimeline Component Integration Guide

## Overview

The DocumentVersionTimeline is a sophisticated Livewire component that provides real-time version tracking and management for documents. It features 60-second polling, incremental updates, and seamless integration with the existing DocumentComponent.

## Features

- **Real-time Polling**: Automatically checks for new versions every 60 seconds
- **Incremental Updates**: Only fetches new versions to prevent full refreshes
- **Content Preview**: Generates intelligent previews from EditorJS blocks
- **User Information**: Shows author details with ULID reference handling
- **Current Version Tracking**: Visual indicators for the currently active version
- **Performance Optimized**: Limits to 20 versions and efficient database queries

## Usage

### Basic Integration

```php
// Include in your document view
<livewire:document-version-timeline 
    :documentId="$document->id" 
    :currentEditingVersion="$currentEditingVersion" />
```

### With DocumentComponent Integration

```php
// In your DocumentComponent
public ?string $currentEditingVersion = null;

#[On('document-version-changed')]
public function handleVersionChange(string $versionId): void
{
    $this->currentEditingVersion = $versionId;
    
    // Load version content for editing
    $version = DocumentVersion::find($versionId);
    if ($version) {
        $this->content = $version->content;
        $this->dispatch('version-content-loaded', content: $version->content);
    }
}
```

### Component Props

| Prop | Type | Required | Description |
|------|------|----------|-------------|
| `documentId` | string | Yes | ULID of the document |
| `currentEditingVersion` | string\|null | No | Currently active version ID |

### Component Methods

| Method | Parameters | Description |
|--------|------------|-------------|
| `startPolling()` | None | Begins 60-second polling cycle |
| `stopPolling()` | None | Stops the polling cycle |
| `checkForNewVersions()` | None | Manual check for new versions |
| `handleVersionSelection(versionId)` | string | Switches to selected version |

### Component Events

| Event | Data | Description |
|-------|------|-------------|
| `document-version-changed` | `{versionId}` | Fired when user selects a version |
| `new-versions-found` | `{versions[]}` | Fired when new versions are detected |
| `compare-versions` | None | Fired when user wants to compare versions |

## Frontend Features

### Alpine.js Integration

The component uses Alpine.js for:
- Client-side polling management
- Real-time UI updates
- Version selection handling
- Visual animations and transitions

### UI Elements

1. **Header**: Shows version count and polling status
2. **Timeline**: Scrollable list of versions with visual timeline
3. **Version Cards**: Display content preview, author, timestamps, and metadata
4. **Footer**: Controls for comparing versions and polling management

### Visual Indicators

- **Current Version**: Blue highlight with "Current" badge
- **New Versions**: Green pulse animation for recent versions
- **Auto-save**: Amber badge for automatically saved versions
- **Polling Status**: Live indicator in header

## Database Schema

### DocumentVersion Model

```sql
CREATE TABLE document_versions (
    id CHAR(26) PRIMARY KEY,
    document_id CHAR(26) NOT NULL,
    version_number BIGINT NOT NULL,
    content JSON NOT NULL,
    created_by CHAR(26) NOT NULL,
    created_at TIMESTAMP NOT NULL,
    
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    UNIQUE KEY (document_id, version_number)
);
```

### Model Configuration

```php
class DocumentVersion extends Model
{
    use HasUlids, LivesInOriginalDB;

    public $timestamps = false;

    protected $fillable = [
        'document_id',
        'content',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'content' => 'array',
        'created_at' => 'datetime',
    ];
}
```

## Performance Considerations

1. **Version Limit**: Component loads only the 20 most recent versions
2. **Incremental Loading**: Only new versions since last check are fetched
3. **Efficient Queries**: Uses indexed queries with proper ordering
4. **Caching**: Component caches last check timestamp to minimize database hits

## Content Preview Generation

The component intelligently generates previews from EditorJS content:

1. **Text Extraction**: Focuses on paragraph and header blocks
2. **HTML Stripping**: Removes HTML tags for clean previews
3. **Length Limiting**: Truncates to 100 characters with ellipsis
4. **Fallback Handling**: Shows block count for non-text content

## Error Handling

- **Database Errors**: Logged with context, returns empty arrays
- **Missing Users**: Shows "Unknown User" for missing authors
- **Invalid Content**: Graceful fallback for malformed EditorJS content
- **Polling Failures**: Automatic retry on next interval

## Customization

### Styling

The component uses Tailwind CSS with proper dark mode support:
- Zinc color palette for neutral elements
- Sky colors for primary actions and current state
- Emerald for success states
- Amber for warnings

### Polling Interval

```javascript
// Default: 60 seconds (60000ms)
// Can be modified in Alpine.js data
pollInterval: 60000
```

### Version Display Limit

```php
// In component constructor
public int $maxVisibleVersions = 5; // Show/hide toggle at 5 versions
```

## Testing

### Unit Tests

The component includes comprehensive tests:
- Component mounting and initialization
- Polling start/stop functionality
- Version selection handling
- Content preview generation
- Performance limits verification

### Integration Tests

Recommended integration tests:
- Document save creating new versions
- Real-time updates between multiple users
- Version switching functionality
- Polling behavior under load

## Security

1. **Authorization**: Inherits document permissions from parent component
2. **Data Validation**: All version data is validated before display
3. **XSS Prevention**: Content previews are properly escaped
4. **CSRF Protection**: All Livewire actions are CSRF protected

## Browser Support

- **Modern Browsers**: Full support with all animations
- **Legacy Browsers**: Graceful degradation without animations
- **Mobile**: Responsive design with touch-friendly interactions

## Dependencies

- Laravel 12.x
- Livewire 3.x
- Alpine.js 3.x
- TailwindCSS 4.x
- DocumentVersion model with LivesInOriginalDB trait

## Troubleshooting

### Common Issues

1. **Polling Not Working**: Check JavaScript console for errors
2. **Versions Not Loading**: Verify DocumentVersion model and database
3. **Performance Issues**: Consider reducing version limit or optimizing queries
4. **UI Not Updating**: Ensure Alpine.js is properly loaded

### Debug Mode

Enable debug logging:

```php
// In component
Log::info('Document version polling started', ['document_id' => $this->documentId]);
```

This comprehensive integration provides a robust, real-time version management system that enhances the document editing experience with minimal performance impact.