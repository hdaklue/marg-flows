# 🎬 Video Service

A comprehensive Laravel video processing ecosystem with fluent APIs, batch conversions, resolution-specific constraints, and real-time monitoring.

## 🚀 Quick Start

```php
use App\Services\Video\Facades\{Video, ResolutionManager};

// Simple video conversion
$results = ResolutionManager::fromDisk('input.mp4')
    ->to720p()
    ->to480p()
    ->onStepSuccess(fn($step) => logger()->info("✅ Done: {$step->getHumanFileSize()}"))
    ->saveTo('outputs/');
```

## 🏗️ Architecture

### Core Components

1. **Video Service** - Individual video operations (resize, crop, trim, watermark)
2. **ResolutionManager** - Batch conversion orchestrator with event callbacks  
3. **Resolution Classes** - Smart resolution definitions with constraints and quality tiers
4. **DirectoryManager** - Tenant/document-specific storage paths
5. **VideoStorageStrategy** - File storage with variants (original, conversions, thumbnails)

### Service Flow
```
DirectoryManager → ResolutionManager → Resolution Classes → Video Operations → FFmpeg Processing
     (paths)           (orchestration)      (constraints)        (operations)        (encoding)
```

## 📚 API Reference

### ResolutionManager (Primary API)

```php
// Factory Methods
ResolutionManager::fromDisk(string $path, string $disk = 'local')
ResolutionManager::from(string $path, string $disk = 'local')

// Resolution Methods  
->to1080p()     // Full HD (1920x1080) - High quality, 4.5Mbps
->to720p()      // HD (1280x720) - High quality, 2.5Mbps
->to480p()      // SD (854x480) - Medium quality, 1Mbps
->to360p()      // Low (640x360) - Low quality, 600kbps
->to240p()      // Very low (426x240) - Low quality, 300kbps
->to144p()      // Minimal (256x144) - Ultra low, 150kbps
->to1440p()     // QHD (2560x1440) - Very high, 8Mbps
->to2K()        // 2K (2048x1080) - Very high, 12Mbps
->to4K()        // 4K UHD (3840x2160) - Ultra high, 15Mbps
->addConversion(ConversionContract $conversion)

// Configuration
->withNamingStrategy(NamingPattern $pattern)
->onStepSuccess(\Closure $callback)
->onStepFailure(\Closure $callback)

// Execution
->saveTo(string $directory = '')
->saveSuccessful(string $directory = '')  // Only successful conversions
->saveFailed(string $directory = '')      // Only failed conversions
```

### Video Service (Individual Operations)

```php
Video::fromDisk('input.mp4')
    ->resize(Dimension::from(1920, 1080))
    ->resizeToWidth(1280)
    ->resizeToHeight(720)
    ->scale(new ScaleProportional(0.5))
    ->crop(0, 0, Dimension::from(800, 600))
    ->trim(10.0, 30.0)
    ->watermark('/path/to/logo.png', 'bottom-right', 0.8)
    ->convert(new Resolution720p())
    ->save('output.mp4');
```

### DirectoryManager (Storage Paths)

```php
// Build tenant-specific storage paths
$videoDir = DirectoryManager::document($tenantId)
    ->forDocument($documentId)
    ->videos()
    ->asConversions()
    ->getDirectory(); // "abc123/documents/def456/videos/proc"
```

## 🎯 Usage Examples

### Basic Batch Conversion

```php
$results = ResolutionManager::fromDisk('sample-video.mp4')
    ->to1080p()
    ->to720p()
    ->to480p()
    ->saveTo();

foreach ($results as $result) {
    echo "✅ {$result->getConversionName()}: {$result->getHumanFileSize()}\n";
}
```

### Advanced with Event Callbacks

```php
$results = ResolutionManager::fromDisk('input.mp4', 'private')
    ->to1080p()
    ->to720p()
    ->to480p()
    ->withNamingStrategy(NamingPattern::Detailed)
    ->onStepSuccess(function($step) {
        logger()->info("Conversion complete", [
            'conversion' => $step->getConversionName(),
            'size' => $step->getHumanFileSize(),
            'path' => $step->output_path
        ]);
        
        // Send real-time notification
        broadcast(new VideoConversionComplete($step));
    })
    ->onStepFailure(function($step) {
        logger()->error("Conversion failed: {$step->error}");
        
        // Alert administrators
        Mail::to('admin@example.com')->send(
            new VideoConversionFailed($step)
        );
    })
    ->saveTo('outputs/');
```

### Complete Enterprise Workflow

```php
// 1. Build tenant-specific directory
$outputDir = DirectoryManager::document($tenantId)
    ->forDocument($documentId)
    ->videos()
    ->asConversions()
    ->getDirectory();

// 2. Process with monitoring
$results = ResolutionManager::fromDisk('original.mp4')
    ->to1080p()
    ->to720p()
    ->to480p()
    ->onStepSuccess(function($step) use ($tenantId, $documentId) {
        // Update database with conversion status
        VideoConversion::create([
            'tenant_id' => $tenantId,
            'document_id' => $documentId,
            'conversion_type' => $step->getConversionName(),
            'file_path' => $step->output_path,
            'file_size' => $step->size,
            'status' => 'completed'
        ]);
    })
    ->saveTo($outputDir);

// 3. Store results using VideoStorageStrategy
$storage = DirectoryManager::document($tenantId)
    ->forDocument($documentId)
    ->videos()
    ->asConversions();

foreach ($results as $result) {
    if ($result->isSuccessful()) {
        // Move to organized storage structure
        $finalPath = $storage->store($result->output_path);
        echo "Stored: {$finalPath}\n";
    }
}
```

### Custom Resolutions

```php
use App\Services\Video\Resolutions\AbstractResolution;
use App\Services\Video\ValueObjects\Dimension;
use App\Services\Video\Enums\BitrateEnum;

class CustomUltraHDResolution extends AbstractResolution
{
    protected string $format = 'mp4';
    protected string $quality = 'ultra';
    protected ?Dimension $dimension;
    protected ?int $bitrate;

    public function __construct()
    {
        $this->dimension = Dimension::from(3840, 2160);
        $this->bitrate = BitrateEnum::ULTRA_HIGH_4K->value;
        $this->allowScaleUp = false;
        $this->maintainAspectRatio = true;
    }

    public function getFilter()
    {
        return ['scale', $this->dimension->getWidth() . ':' . $this->dimension->getHeight()];
    }
}

ResolutionManager::fromDisk('input.mp4')
    ->addConversion(new CustomUltraHDResolution())
    ->to1080p()
    ->saveTo();
```

## 📊 ResolutionData DTO

Each conversion returns a structured DTO:

```php
ResolutionData {
    +conversion: "App\Services\Video\Resolutions\Resolution720p"
    +output_path: "sample-video_720p.mp4"
    +status: "success"
    +size: 13123998
    +error: null
}

// Helper methods
$result->isSuccessful()         // bool
$result->isFailed()             // bool  
$result->getHumanFileSize()     // "12.5 MB"
$result->getConversionName()    // "Resolution720p"
$result->getOutputFilename()    // "sample-video_720p.mp4"
$result->toJson()               // JSON string
```

## 🔧 Configuration

### Naming Patterns

```php
use App\Services\Video\Enums\NamingPattern;

NamingPattern::Quality       // video_720p.mp4
NamingPattern::Dimension     // video_1280x720.mp4
NamingPattern::Conversion    // video_Resolution720p.mp4
NamingPattern::Detailed      // video_1280x720_high_2500kbps.mp4
NamingPattern::Timestamped   // video_20241224123456.mp4
NamingPattern::Simple        // video_converted.mp4
```

### Bitrate Management

```php
use App\Services\Video\Enums\BitrateEnum;

// Predefined quality tiers
BitrateEnum::ULTRA_LOW_144P->getKbps()    // 150 kbps
BitrateEnum::HIGH_720P->getMbps()         // 2.5 Mbps
BitrateEnum::ULTRA_HIGH_4K->getKbps()     // 15000 kbps

// Dynamic bitrate calculation
$bitrate = BitrateEnum::calculateForPixels(1920 * 1080, 'high'); // ~4500 kbps
$enum = BitrateEnum::forResolution('1080p'); // BitrateEnum::HIGH_1080P
```

### Available Scaling Strategies

```php
use App\Services\Video\Strategies\{ScaleProportional, ScaleToFit, ScaleToFill, ScaleExact, ScaleToAspectRatio};

new ScaleProportional(0.5)              // 50% of original size
new ScaleToFit($maxDimension)           // Fit within bounds
new ScaleToFill($dimension)             // Fill dimensions (may crop)
new ScaleExact(1920, 1080)              // Exact dimensions (no aspect ratio preservation)
new ScaleToAspectRatio(AspectRatio::WIDESCREEN_16_9)  // Scale to specific aspect ratio
```

### Resolution Constraints

Each resolution class includes smart constraints to prevent unnecessary processing:

```php
// Resolution classes automatically handle constraints
$resolution720p = new Resolution720p();

// Check constraints before processing
if ($resolution720p->wouldScaleUp($currentDimension)) {
    logger()->info('720p conversion would scale up - consider skipping');
}

// Get calculated final dimensions considering constraints
$finalDimension = $resolution720p->calculateFinalDimension($currentDimension);

// Access constraint settings
$constraints = $resolution720p->getConstraints();
// [
//     'allow_scale_up' => false,
//     'max_dimension' => ['width' => 1920, 'height' => 1080],
//     'min_dimension' => null,
//     'maintain_aspect_ratio' => true
// ]
```

### Resolution Value Object

The new Resolution value object provides orientation-aware dimensions:

```php
use App\Services\Video\ValueObjects\Resolution;

// Create resolutions for different orientations
$landscape144p = Resolution::create144p('landscape'); // 256x144
$portrait144p = Resolution::create144p('portrait');   // 144x256
$square144p = Resolution::create144p('square');       // 144x144

// Access properties
$landscape144p->dimension->getWidth();    // 256
$landscape144p->bitrate->getKbps();       // 150
$landscape144p->qualityTier;              // 'ultra_low'
```

## 🚨 Error Handling

### Validation Errors
```php
try {
    $results = ResolutionManager::fromDisk('video.mp4')
        ->to720p()
        ->saveTo();
} catch (InvalidArgumentException $e) {
    logger()->error("Invalid request: {$e->getMessage()}");
}
```

### Step-by-Step Error Handling
```php
$results = ResolutionManager::fromDisk('video.mp4')
    ->to1080p()
    ->to720p()
    ->onStepFailure(function($step) {
        if (str_contains($step->error, 'codec')) {
            // Handle codec issues
            $this->handleCodecError($step);
        } elseif (str_contains($step->error, 'disk space')) {
            // Handle storage issues  
            $this->handleStorageError($step);
        }
    })
    ->saveTo();
```

### Filtering Results
```php
// Get only successful conversions
$successful = ResolutionManager::fromDisk('video.mp4')
    ->to1080p()
    ->to720p() 
    ->to480p()
    ->saveSuccessful();

// Get only failed conversions for debugging
$failed = ResolutionManager::fromDisk('video.mp4')
    ->to4K() // Might fail due to upscaling constraints
    ->to1080p()
    ->saveFailed();
```

## 🎨 Best Practices

### 1. Always Use Event Callbacks
```php
// ✅ Good - Monitor progress and handle errors
ResolutionManager::fromDisk('video.mp4')
    ->to720p()
    ->onStepSuccess(fn($step) => logger()->info("Done: {$step->toJson()}"))
    ->onStepFailure(fn($step) => logger()->error("Failed: {$step->error}"))
    ->saveTo();

// ❌ Avoid - Silent failures
ResolutionManager::fromDisk('video.mp4')->to720p()->saveTo();
```

### 2. Specify Correct Storage Disks
```php
// ✅ Good - Explicit disk specification
ResolutionManager::fromDisk('video.mp4', 'private')
    ->to720p()
    ->saveTo();

// ❌ Avoid - Assuming default disk
ResolutionManager::fromDisk('private/video.mp4') // Won't find file
    ->to720p()
    ->saveTo();
```

### 3. Use Appropriate Naming Strategies
```php
// ✅ Good - Descriptive naming for production
ResolutionManager::fromDisk('video.mp4')
    ->withNamingStrategy(NamingPattern::Detailed) // video_1280x720_high_5000kbps.mp4
    ->to720p()
    ->saveTo();

// ✅ Good - Simple naming for development
ResolutionManager::fromDisk('video.mp4')
    ->withNamingStrategy(NamingPattern::Simple) // video_converted.mp4
    ->to720p()
    ->saveTo();
```

### 4. Leverage Directory Structure
```php
// ✅ Good - Organized tenant/document structure
$outputDir = DirectoryManager::document($tenantId)
    ->forDocument($documentId)
    ->videos()
    ->asConversions()
    ->getDirectory();

ResolutionManager::fromDisk('video.mp4')
    ->to720p()
    ->saveTo($outputDir);
```

### 5. Chain Operations Fluently
```php
// ✅ Good - Readable fluent chain
ResolutionManager::fromDisk('input.mp4')
    ->to1080p()
    ->to720p()
    ->to480p()
    ->withNamingStrategy(NamingPattern::Quality)
    ->onStepSuccess(fn($step) => $this->logSuccess($step))
    ->onStepFailure(fn($step) => $this->handleFailure($step))
    ->saveTo('outputs/');
```

## 🔍 Debugging

### Enable Detailed Logging
```php
ResolutionManager::fromDisk('video.mp4')
    ->to720p()
    ->onStepSuccess(function($step) {
        logger()->debug('Conversion step completed', [
            'conversion' => $step->conversion,
            'output_path' => $step->output_path,
            'file_size_bytes' => $step->size,
            'file_size_human' => $step->getHumanFileSize(),
            'duration_ms' => microtime(true) * 1000 - $this->startTime
        ]);
    })
    ->onStepFailure(function($step) {
        logger()->error('Conversion step failed', [
            'conversion' => $step->conversion,
            'error' => $step->error,
            'output_path' => $step->output_path
        ]);
    })
    ->saveTo();
```

### Check File Existence
```php
// Verify source file exists before processing
if (!Storage::disk('private')->exists('video.mp4')) {
    throw new InvalidArgumentException('Source video not found');
}

$results = ResolutionManager::fromDisk('video.mp4', 'private')
    ->to720p()
    ->saveTo();
```

## 🧪 Testing

### Feature Tests
```php
test('can convert video to multiple resolutions', function() {
    // Setup test video
    Storage::fake('local');
    $testVideo = UploadedFile::fake()->create('test.mp4', 1000, 'video/mp4');
    Storage::disk('local')->putFileAs('', $testVideo, 'test-video.mp4');
    
    // Test conversion
    $results = ResolutionManager::fromDisk('test-video.mp4')
        ->to720p()
        ->to480p()
        ->saveTo();
    
    // Assertions
    expect($results)->toHaveCount(2);
    expect($results[0]->isSuccessful())->toBeTrue();
    expect($results[0]->size)->toBeGreaterThan(0);
});
```

### Unit Tests
```php
test('resolution data dto provides correct helper methods', function() {
    $data = ResolutionData::success(
        'App\\Services\\Video\\Conversions\\Conversion720p',
        'test-video_720p.mp4', 
        1048576 // 1MB
    );
    
    expect($data->isSuccessful())->toBeTrue();
    expect($data->getConversionName())->toBe('Conversion720p');
    expect($data->getHumanFileSize())->toBe('1.00 MB');
    expect($data->getOutputFilename())->toBe('test-video_720p.mp4');
});
```

---

## 📁 Directory Structure

```
app/Services/Video/
├── README.md                           # This file
├── VideoServiceProvider.php            # Service registration
├── Video.php                          # Main video service class
├── VideoManager.php                   # Legacy video manager
├── config/                            # Configuration files
│   └── video.php
├── Examples/                          # Usage examples
│   └── VideoProcessingExample.php
├── Contracts/                         # Interface definitions
│   ├── ConversionContract.php
│   ├── ScaleStrategyContract.php
│   └── VideoOperationContract.php
├── DTOs/                              # Data transfer objects
│   └── ResolutionData.php
├── Enums/                             # Enumeration classes
│   ├── NamingPattern.php
│   └── BitrateEnum.php
├── Facades/                           # Laravel facades
│   ├── Video.php
│   └── ResolutionManager.php
├── Services/                          # Core service classes
│   ├── VideoEditor.php
│   ├── VideoNamingService.php
│   └── ResolutionManager.php
├── Operations/                        # Video operation classes
│   ├── AbstractVideoOperation.php
│   ├── ResolutionOperation.php
│   ├── ResizeOperation.php
│   ├── ResizeToWidthOperation.php
│   ├── ResizeToHeightOperation.php
│   ├── ScaleOperation.php
│   ├── TrimOperation.php
│   ├── CropOperation.php
│   └── WatermarkOperation.php
├── Pipeline/                          # Operation pipeline
│   └── VideoOperationPipeline.php
├── Strategies/                        # Scaling strategies
│   ├── ScaleProportional.php
│   ├── ScaleToFit.php
│   ├── ScaleToFill.php
│   ├── ScaleExact.php
│   └── ScaleToAspectRatio.php
├── Resolutions/                       # Resolution definitions with constraints
│   ├── AbstractResolution.php
│   ├── Resolution144p.php
│   ├── Resolution240p.php
│   ├── Resolution360p.php
│   ├── Resolution480p.php
│   ├── Resolution720p.php
│   ├── Resolution1080p.php
│   ├── Resolution1440p.php
│   ├── Resolution2K.php
│   ├── Resolution4K.php
│   └── ResolutionMobile.php
└── ValueObjects/                      # Value object classes
    ├── Dimension.php
    ├── AspectRatio.php
    └── Resolution.php
```

---

## ⚡ Performance Tips

1. **Process in background jobs** for large files
2. **Use appropriate conversion presets** for your use case
3. **Monitor disk space** during batch conversions
4. **Consider chunked processing** for very long videos
5. **Cache frequently converted resolutions**
6. **Use event callbacks** for real-time progress updates
7. **Clean up temporary files** after processing

---

## 🔗 Related Services

- **DirectoryManager** (`app/Services/Directory/`) - File organization and tenant isolation
- **FileSize Helper** (`app/Support/FileSize.php`) - Human-readable file size formatting
- **Laravel FFmpeg** - Underlying video processing engine

---

**Built with ❤️ for enterprise video processing**