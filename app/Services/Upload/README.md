# Upload Service Documentation

This directory contains a comprehensive file upload system designed for Laravel applications with multi-tenant support, chunked uploads, and multiple progress tracking strategies.

## 🏗️ Architecture Overview

The Upload service is built using the Strategy Pattern and provides:
- **Chunked file uploads** with memory-efficient processing
- **Multi-tenant support** with tenant-specific directory management
- **Multiple progress tracking strategies** (HTTP, WebSocket, Log-based)
- **Plan-based configuration** (Simple, Advanced, Ultimate)
- **File type optimization** (Images, Videos, Documents)

## 📁 Directory Structure

```
Upload/
├── ChunkAssembler.php           # Memory-efficient chunk assembly
├── ChunkConfigManager.php       # Plan-based upload configurations
├── UploadSessionManager.php     # Driver manager for progress strategies
├── UploadSessionService.php     # Core upload service logic
├── Contracts/
│   └── ProgressStrategyContract.php  # Interface for progress strategies
├── DTOs/
│   ├── ChunkConfig.php         # Upload configuration DTO
│   ├── ChunkData.php           # Chunk information DTO
│   └── ProgressData.php        # Progress tracking DTO
├── Facades/
│   └── UploadSessionManager.php # Laravel facade for easy access
└── Strategies/
    └── Progress/
        ├── HttpResponseProgressStrategy.php  # Cache-based progress tracking
        ├── LogProgressStrategy.php          # Log-based progress tracking
        ├── SimpleProgressStrategy.php       # Basic Redis progress tracking
        └── WebSocketProgressStrategy.php    # Real-time WebSocket progress
```

## 🚀 Core Components

### 1. ChunkAssembler

**Purpose**: Efficiently assembles uploaded chunks into final files without exhausting memory.

**Key Features**:
- Generator-based streaming to handle large files
- Pre-validation of all chunks before assembly
- Automatic chunk cleanup after assembly
- Stream-based file writing to prevent memory exhaustion

**Use Cases**:
- Large file uploads (videos, documents, archives)
- Memory-constrained environments
- High-concurrency upload scenarios

**Implementation Example**:
```php
$finalPath = ChunkAssembler::assemble(
    sessionId: 'upload_session_123',
    fileName: 'video.mp4',
    totalChunks: 100,
    chunkDirectory: 'chunks/session_123',
    storeDirectory: 'uploads/videos'
);
```

### 2. ChunkConfigManager

**Purpose**: Provides predefined upload configurations based on subscription plans and file types.

**Strategies**:
- **Simple Plan**: 50MB max, 1MB chunks, 2 concurrent uploads
- **Advanced Plan**: 250MB max, 5MB chunks, 3 concurrent uploads  
- **Ultimate Plan**: 2GB max, 10MB chunks, 5 concurrent uploads

**Specialized Configurations**:
- **Images**: Smaller files, no chunking for most cases
- **Videos**: Larger files, always chunked, longer timeouts

**Use Cases**:
- Subscription-based file upload limits
- File type optimization
- Performance tuning based on user plans

**Implementation Example**:
```php
// Get configuration for a user's plan
$config = ChunkConfigManager::forPlan('ultimate');

// Get optimized config for video uploads
$videoConfig = ChunkConfigManager::forVideos('advanced');

// Get optimized config for images
$imageConfig = ChunkConfigManager::forImages('simple');
```

### 3. UploadSessionManager

**Purpose**: Laravel Manager class that provides different progress tracking strategies.

**Available Drivers**:
- `http`: Cache-based progress tracking for API endpoints
- `websocket`: Real-time progress updates via WebSockets
- `log`: File-based progress logging
- `redis`: Simple Redis-based progress tracking

**Use Cases**:
- Multi-channel progress tracking
- Strategy switching based on client capabilities
- Testing different progress notification methods

**Implementation Example**:
```php
// Using specific strategy
$service = UploadSessionManager::start('websocket', 'tenant_123');

// Using default strategy
$service = UploadSessionManager::start('http', 'tenant_456')
    ->storeIn('uploads/documents');
```

### 4. UploadSessionService

**Purpose**: Core service that orchestrates the entire upload process.

**Key Features**:
- Multi-tenant upload support
- Single file and chunked upload handling
- Automatic progress tracking
- Session management and cleanup
- Memory-efficient chunk processing

**Use Cases**:
- Primary upload interface
- Session lifecycle management
- Progress monitoring
- File processing coordination

**Implementation Example**:
```php
$service = new UploadSessionService(new HttpResponseProgressStrategy());

// Single file upload
$path = $service
    ->forTenant('tenant_123')
    ->storeIn('uploads/documents')
    ->upload($uploadedFile);

// Chunked upload
$sessionId = $service->initSession('large_file.zip', 50, 524288000);
foreach ($chunks as $index => $chunk) {
    $service->storeChunk($sessionId, $chunk, $index);
}
$finalPath = $service->assembleFile($sessionId, 'large_file.zip', 50);
```

## 📊 Progress Tracking Strategies

### HttpResponseProgressStrategy

**Purpose**: Cache-based progress tracking for HTTP API endpoints.

**Implementation**:
- Stores progress in Laravel cache
- 2-hour TTL for session data
- Accessible via API endpoints
- Includes detailed logging

**Use Cases**:
- REST API file uploads
- AJAX upload progress bars
- Polling-based progress updates

### WebSocketProgressStrategy

**Purpose**: Real-time progress updates via WebSocket connections.

**Implementation** (Template):
- WebSocket channel setup per session
- Real-time progress broadcasting
- Event-driven updates
- Automatic cleanup on completion

**Use Cases**:
- Real-time upload dashboards
- Live progress notifications
- Interactive upload interfaces

### LogProgressStrategy

**Purpose**: File-based progress logging for debugging and monitoring.

**Use Cases**:
- Upload debugging
- Audit trail requirements
- System monitoring
- Troubleshooting failed uploads

### SimpleProgressStrategy

**Purpose**: Basic Redis-based progress tracking.

**Use Cases**:
- High-performance scenarios
- Minimal overhead requirements
- Redis-based architectures

## 📋 Data Transfer Objects (DTOs)

### ChunkConfig

Immutable configuration object defining upload parameters:
- Maximum file size
- Chunk size
- Concurrent upload limits
- Retry attempts and timeouts
- Frontend-friendly data conversion

### ChunkData & ChunkInfo

Comprehensive chunk management:
- Session and file metadata
- Individual chunk tracking
- Progress calculation
- Upload status management
- Hash verification support

### ProgressData

Detailed progress information:
- Chunk completion status
- Byte transfer tracking
- Percentage calculation
- Status and error handling
- Time estimation (future feature)

## 🎯 Usage Patterns

### Basic Single File Upload

```php
use App\Services\Upload\Facades\UploadSessionManager;

$path = UploadSessionManager::start('http', 'tenant_123')
    ->storeIn('uploads/documents')
    ->upload($uploadedFile);
```

### Chunked Upload with Progress Tracking

```php
$service = UploadSessionManager::start('websocket', 'tenant_123')
    ->storeIn('uploads/videos');

// Initialize session
$sessionId = $service->initSession('movie.mp4', 100, 1073741824);

// Upload chunks
foreach ($chunks as $index => $chunk) {
    $service->storeChunk($sessionId, $chunk, $index);
    
    // Check progress
    $progress = $service->getProgress($sessionId);
    echo "Progress: {$progress->percentage}%\n";
}

// Assemble final file
if ($service->isComplete($sessionId, 100)) {
    $finalPath = $service->assembleFile($sessionId, 'movie.mp4', 100);
    $service->cleanupSession($sessionId);
}
```

### Plan-Based Configuration

```php
$config = ChunkConfigManager::forVideos('ultimate');

$service = UploadSessionManager::start('http', 'tenant_123')
    ->storeIn('uploads/videos');
    
// Apply configuration limits in your controller/validation
```

## 🔧 Configuration

### Environment Variables

```env
# Storage configuration
CHUNKED_UPLOAD_DISK=local_chunks
UPLOAD_SESSION_DEFAULT=http

# Plan limits (bytes)
SIMPLE_PLAN_MAX_SIZE=52428800     # 50MB
ADVANCED_PLAN_MAX_SIZE=262144000  # 250MB
ULTIMATE_PLAN_MAX_SIZE=2147483648 # 2GB
```

### Cache Configuration

```php
// config/cache.php
'upload_sessions' => [
    'driver' => 'redis',
    'connection' => 'default',
],
```

## 🧪 Testing Strategies

### Unit Tests
- DTO validation and calculations
- Configuration manager plan logic
- Individual strategy implementations

### Feature Tests
- End-to-end upload flows
- Multi-tenant isolation
- Progress tracking accuracy
- Error handling scenarios

### Integration Tests
- Storage disk interactions
- Cache/Redis functionality
- WebSocket broadcasting (if implemented)

## 🚨 Error Handling

The service provides comprehensive error handling:
- **Validation Errors**: Missing configuration, invalid parameters
- **Storage Errors**: Disk space, permissions, network issues
- **Assembly Errors**: Missing chunks, corruption, timeout
- **Progress Errors**: Strategy failures, data corruption

All errors are logged and propagated through the progress tracking system.

## 🔒 Security Considerations

- **File Type Validation**: Implement MIME type checking
- **Size Limits**: Enforce plan-based restrictions
- **Path Traversal**: Sanitize file names and paths
- **Tenant Isolation**: Ensure proper multi-tenant separation
- **Cleanup**: Automatic removal of temporary files

## 🚀 Performance Optimizations

- **Memory Management**: Generator-based chunk processing
- **Concurrent Uploads**: Plan-based concurrency limits
- **Caching**: Efficient progress data storage
- **Cleanup**: Automatic temporary file removal
- **Streaming**: Stream-based file assembly

## 🔮 Future Enhancements

- **Resume Capability**: Support for resuming interrupted uploads
- **Compression**: On-the-fly file compression
- **Encryption**: End-to-end file encryption
- **CDN Integration**: Direct-to-CDN upload support
- **Virus Scanning**: Integrated malware detection
- **Thumbnail Generation**: Automatic image/video thumbnails

## 📖 Related Documentation

- [Laravel File Storage](https://laravel.com/docs/filesystem)
- [Laravel Manager Pattern](https://laravel.com/docs/extending)
- [Strategy Pattern in PHP](https://refactoring.guru/design-patterns/strategy/php/example)
- [Multi-Tenant Architecture](https://laravel.com/docs/database#configuration)