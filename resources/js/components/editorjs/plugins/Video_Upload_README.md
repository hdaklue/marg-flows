# VideoUpload Plugin for EditorJS

A comprehensive video upload plugin for EditorJS that supports chunked uploads, real-time progress tracking, and Video.js integration for video playback.

## Overview

The VideoUpload plugin provides a complete video upload solution for EditorJS with support for:
- **Single and chunked file uploads**
- **Real-time progress tracking**
- **Session-based upload management**
- **Video.js integration for playback**
- **Responsive design with dark/light mode support**
- **Multi-language support (English/Arabic)**

## Architecture

### Core Components

```
VideoUpload (Main Plugin)
├── SessionUploadStrategy (Upload Strategy)
├── ResizableTune (Resize Functionality)
├── Video Validation (Format/Size Validation)
└── Progress Management (UI/Status Updates)
```

### Upload Flow Lifecycle

#### 1. Initialization Phase
```javascript
constructor() → initializeLocalization() → setupConfig()
```
- Loads user language preferences
- Merges server configuration with defaults
- Initializes upload strategies

#### 2. File Selection Phase
```javascript
File Selection → validateVideoFile() → determineUploadStrategy()
```
- **Drag & Drop**: Block-level drag/drop handling
- **File Input**: Click-to-browse functionality
- **Clipboard Paste**: Direct paste support
- **Validation**: Format and size validation using `VIDEO_VALIDATION_CONFIG`

#### 3. Upload Phase
```javascript
handleUpload() → SessionUploadStrategy.execute() → Progress Updates
```
- **Session Creation**: Creates upload session via `/video-upload-sessions`
- **Strategy Selection**: Single vs chunked based on file size
- **Progress Tracking**: Real-time updates via callbacks
- **Error Handling**: Retry logic and user feedback

#### 4. Processing Phase
```javascript
Upload Complete → Server Processing → Status Polling → Final Rendering
```
- **Video Processing**: Metadata extraction, thumbnail generation
- **Status Updates**: Phase-specific progress tracking
- **Completion**: Video player rendering with Video.js

## Routes and Endpoints

### Primary Upload Routes

#### 1. Session Management
```javascript
POST /video-upload-sessions
```
**Purpose**: Create upload session and determine upload strategy
**Request Body**:
```json
{
  "filename": "video.mp4",
  "fileSize": 52428800,
  "fileType": "video/mp4"
}
```
**Response**:
```json
{
  "session_id": "01HXXX...",
  "upload_type": "chunk|single",
  "max_single_file_size": 52428800,
  "chunk_size": 5242880
}
```

#### 2. Single File Upload
```javascript
POST /documents/{document}/upload-video-single
```
**Purpose**: Upload entire video file in one request
**Content-Type**: `multipart/form-data`
**Fields**:
- `video`: File blob
- `session_id`: Upload session ID

#### 3. Chunked Upload
```javascript
POST /documents/{document}/upload-video-chunk
```
**Purpose**: Upload video file in chunks
**Content-Type**: `multipart/form-data`
**Fields**:
- `chunk`: Chunk blob
- `session_id`: Upload session ID
- `chunk_index`: Current chunk number
- `total_chunks`: Total number of chunks

#### 4. Session Status
```javascript
GET /video-upload-sessions/{sessionId}/status
```
**Purpose**: Poll upload progress and processing status
**Response**:
```json
{
  "status": "uploading|processing|completed|failed",
  "phase": "chunk_upload|video_processing|metadata_extraction|complete",
  "progress": 75,
  "data": {
    "filename": "unique_filename.mp4",
    "width": 1920,
    "height": 1080,
    "duration": 120.5,
    "format": "mp4",
    "aspect_ratio": "16:9"
  }
}
```

### Secondary Routes

#### 5. File Serving
```javascript
GET /documents/{document}/videos/{filename}
```
**Purpose**: Secure video file serving with authentication

#### 6. File Deletion
```javascript
DELETE /delete-video
```
**Purpose**: Remove uploaded video files
**Request Body**:
```json
{
  "path": "documents/videos/tenant_id/document_id/filename.mp4"
}
```

## Upload Strategies

### SessionUploadStrategy

The primary upload strategy that handles both single and chunked uploads:

#### Configuration
```javascript
{
  endpoints: {
    createSession: '/video-upload-sessions',
    sessionStatus: '/video-upload-sessions',
    single: '/documents/{document}/upload-video-single',
    chunk: '/documents/{document}/upload-video-chunk'
  },
  chunkSize: 5242880,           // 5MB chunks
  maxFileSize: 104857600,       // 100MB max
  maxSingleFileSize: 52428800   // 50MB single upload threshold
}
```

#### Upload Decision Logic
```javascript
const uploadType = fileSize >= maxSingleFileSize ? 'chunk' : 'single';
```

#### Progress Callbacks
```javascript
strategy.setProgressCallback((progress) => {
  // Update progress bar: 0-100%
});

strategy.setStatusCallback((message, phase) => {
  // Update status text and phase indicator
});
```

## User Interface States

### 1. Upload Interface
- **Drag & Drop Area**: Visual feedback for file dropping
- **File Browser**: Click-to-select functionality
- **Format Display**: Shows supported formats and size limits

### 2. Progress Interface
- **Animated Spinner**: CSS animations during upload
- **Progress Messages**: Phase-specific status updates
- **Upload Metrics**: Speed, ETA, uploaded bytes

### 3. Video Display
- **Thumbnail Container**: Responsive aspect ratio container
- **Modern Video Indicator**: Custom styled video preview
- **Play Button Overlay**: Click-to-play functionality

### 4. Modal Player
- **Video.js Integration**: Full-featured video player
- **Responsive Design**: Maintains aspect ratio across devices
- **Controls**: Play/pause, seeking, fullscreen, playback speed

## Error Handling

### Validation Errors
- **Format Validation**: Checks against `VIDEO_VALIDATION_CONFIG.supportedExtensions`
- **Size Validation**: Enforces server-defined limits
- **User Feedback**: Localized error messages

### Upload Errors
- **Network Failures**: Automatic retry with exponential backoff
- **Server Errors**: User-friendly error display
- **Progress Recovery**: Resume from last successful chunk

### Playback Errors
- **Codec Issues**: Fallback to download link
- **Network Issues**: Retry mechanisms
- **Browser Compatibility**: Format suggestions

## Configuration

### Server Configuration
```javascript
{
  endpoints: { /* API endpoints */ },
  maxFileSize: 104857600,
  maxSingleFileSize: 52428800,
  chunkSize: 5242880,
  secureFileEndpoint: '/documents/{document}',
  additionalRequestHeaders: { /* auth headers */ }
}
```

### Supported Formats
- **MP4** (H.264/H.265)
- **WebM** (VP8/VP9)
- **OGV** (Theora)

### Validation Config
```javascript
VIDEO_VALIDATION_CONFIG = {
  supportedExtensions: ['mp4', 'webm', 'ogv'],
  supportedMimeTypes: ['video/mp4', 'video/webm', 'video/ogg'],
  maxDuration: 3600 // 1 hour
}
```

## Integration Points

### EditorJS Integration
```javascript
// Plugin registration
tools: {
  videoUpload: {
    class: VideoUpload,
    config: serverConfig,
    tunes: ['resizable']
  }
}
```

### Laravel Backend Integration
- **Document Model**: Association with document entities
- **RBAC**: Role-based access control for file operations
- **Multi-tenancy**: Tenant-scoped file organization
- **Queue Processing**: Async video processing jobs

### Video.js Integration
- **Lazy Loading**: Dynamic Video.js import when needed
- **Player Configuration**: Optimized for various video formats
- **Responsive Design**: Fluid player sizing
- **Error Handling**: Graceful fallbacks for unsupported formats

## Performance Considerations

### Upload Optimization
- **Chunked Uploads**: Reduces memory usage and enables resume
- **Parallel Processing**: Non-blocking UI during uploads
- **Progress Throttling**: Efficient UI updates

### Playback Optimization
- **Lazy Loading**: Video.js loaded only when needed
- **Metadata Preloading**: Fast preview generation
- **Aspect Ratio Preservation**: Prevents layout shifts

### Memory Management
- **File Streaming**: No full file loading in memory
- **Cleanup**: Proper disposal of Video.js players
- **Event Handlers**: Cleanup on component destruction

## Localization

### Supported Languages
- **English** (default)
- **Arabic** (RTL support)

### Translation Keys
```javascript
{
  captionPlaceholder: 'Enter video caption...',
  uploadTitle: 'Upload a video',
  statusUploading: 'Uploading video...',
  statusProcessing: 'Processing video...',
  errors: {
    invalidFormat: 'Invalid file format...',
    fileTooLarge: 'File is too large...',
    uploadFailed: 'Upload failed'
  }
}
```

## Security Features

### Authentication
- **Secure Endpoints**: All upload routes require authentication
- **Session Validation**: Server-side session verification
- **File Serving**: Authenticated file access only

### Validation
- **Client-side**: Format and size pre-validation
- **Server-side**: Comprehensive security validation
- **MIME Type Verification**: Prevents malicious file uploads

### Access Control
- **Document Ownership**: User can only upload to owned documents
- **Tenant Isolation**: Multi-tenant file segregation
- **Role Permissions**: RBAC for upload operations

## Dependencies

### Frontend
- **EditorJS**: Core editor framework
- **Video.js**: Video playback library
- **ResizableTune**: Block resizing functionality

### Backend
- **Laravel Framework**: API and file handling
- **Laravel Actions**: Upload action classes
- **Laravel Queues**: Async video processing

### CSS
- **video-upload.css**: Plugin-specific styles
- **video-resize.css**: Resizing functionality styles

## Usage Example

```javascript
// EditorJS configuration
const editor = new EditorJS({
  tools: {
    videoUpload: {
      class: VideoUpload,
      config: {
        endpoints: {
          createSession: '/api/video-upload-sessions',
          single: '/api/documents/{document}/upload-video-single',
          chunk: '/api/documents/{document}/upload-video-chunk'
        },
        maxFileSize: 100 * 1024 * 1024, // 100MB
        secureFileEndpoint: '/api/documents/{document}'
      }
    }
  }
});
```

This documentation provides a comprehensive overview of the VideoUpload plugin's architecture, lifecycle, and integration points with the Laravel backend system.