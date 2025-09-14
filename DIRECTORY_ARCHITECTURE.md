# Directory Manager Architecture Refactoring

## Overview

The DirectoryManager has been successfully refactored into an abstract base class architecture that enforces common patterns while allowing concrete implementations for specific storage needs.

## Architecture Components

### 1. AbstractDirectoryManager (Base Class)

**Location:** `app/Services/Directory/AbstractDirectoryManager.php`

**Purpose:** Defines common patterns and utility methods that all directory managers should have.

**Abstract Methods:**
- `getDisk(): string` - Forces implementations to specify which storage disk to use
- `getBaseDirectory(?string $identifier = null): string` - Enforces base directory logic

**Common Methods Provided:**
- `getAllFiles(?string $identifier = null): array`
- `getSecureUrl(string $identifier, string $type, string $fileName): string`
- `getTemporaryUrl(string $identifier, string $type, string $fileName, int $expiresIn = 1800): string`
- `deleteFile(string $identifier, string $type, string $fileName): bool`
- `getFileContents(string $identifier, string $type, string $fileName): ?string`
- `fileExists(string $identifier, string $type, string $fileName): bool`
- `getFileSize(string $identifier, string $type, string $fileName): ?int`
- `getFilePath(string $identifier, string $type, string $fileName): ?string`

**Protected Helper Methods:**
- `buildFilePath(string $identifier, string $type, string $fileName): string`
- `ensureDirectoryExists(string $identifier, string $type): void`

### 2. Concrete Implementations

#### DirectoryManager (Static Facade)
**Location:** `app/Services/Directory/DirectoryManager.php`

- Extends `AbstractDirectoryManager` 
- Maintains backward compatibility through static methods
- Handles tenant-specific storage with MD5 hashing
- Uses configured document storage disk

#### DocumentDirectoryManager
**Location:** `app/Services/Directory/Managers/DocumentDirectoryManager.php`

- Extends `AbstractDirectoryManager`
- Implements `DocumentDirectoryManagerContract`
- Focuses on tenant-specific document storage operations
- Integrates with `StorageManager` for strategy creation

#### SystemDirectoryManager  
**Location:** `app/Services/Directory/Managers/SystemDirectoryManager.php`

- Extends `AbstractDirectoryManager`
- Implements `SystemDirectoryManagerContract`
- Handles system-wide storage (avatars, temp files)
- No tenant isolation required

## Benefits Achieved

### 1. Enforced Patterns
- All directory managers must implement `getDisk()` and `getBaseDirectory()`
- Consistent file operation methods across all implementations
- Standardized error handling and return types

### 2. Code Reuse
- Common utility methods are implemented once in the base class
- Reduced duplication across different manager implementations
- Consistent behavior for file operations

### 3. Extensibility
- Easy to create new directory managers for specific use cases
- Abstract base class provides foundation while allowing customization
- See `Examples/CustomDirectoryManagerExample.php` for reference

### 4. Backward Compatibility
- Original `DirectoryManager` static facade API unchanged
- Existing code continues to work without modifications
- Gradual migration path available

## Usage Examples

### Creating a New Directory Manager

```php
use App\Services\Directory\AbstractDirectoryManager;

final class ProjectDirectoryManager extends AbstractDirectoryManager
{
    protected function getDisk(): string
    {
        return 'projects';
    }

    protected function getBaseDirectory(?string $identifier = null): string
    {
        return $identifier ? "projects/" . md5($identifier) : 'projects';
    }
}
```

### Using Existing Managers

```php
// Static facade (unchanged)
$strategy = DirectoryManager::document('tenant-123');
$files = DirectoryManager::getAllFiles('tenant-123');

// Dependency injection approach
$documentManager = app(DocumentDirectoryManager::class);
$strategy = $documentManager->document('tenant-123');
$files = $documentManager->getAllFiles('tenant-123');
```

## File Structure

```
app/Services/Directory/
├── AbstractDirectoryManager.php              # Abstract base class
├── DirectoryManager.php                      # Static facade implementation
├── Contracts/                                # Interface definitions
│   ├── DocumentDirectoryManagerContract.php
│   ├── SystemDirectoryManagerContract.php
│   └── StorageManagerContract.php
├── Managers/                                 # Concrete implementations
│   ├── DocumentDirectoryManager.php         # Tenant-specific storage
│   ├── SystemDirectoryManager.php           # System-wide storage
│   └── StorageManager.php                   # Strategy factory
├── Examples/                                 # Usage examples
│   └── CustomDirectoryManagerExample.php
└── Strategies/                               # Storage strategies
    ├── BaseStorageStrategy.php
    ├── DocumentStorageStrategy.php
    ├── AvatarStorageStrategy.php
    └── ...
```

## Testing

The abstract base class enforces proper testing patterns through:
- Consistent method signatures
- Predictable behavior across implementations
- Clear separation of concerns

## Migration Notes

- **No breaking changes** - All existing code continues to work
- **Optional adoption** - New features can use dependency injection approach
- **Gradual refactoring** - Existing static calls can be migrated over time
- **Type safety** - Abstract methods enforce proper implementation

## Key Design Decisions

1. **Abstract methods are protected** - Prevents external access while enforcing implementation
2. **Identifier-based operations** - Flexible parameter that can represent tenant ID, user ID, etc.
3. **Consistent return types** - All methods have well-defined return types with nullability where appropriate
4. **Storage disk abstraction** - Each implementation controls its storage destination
5. **Path building helpers** - Common patterns are abstracted into reusable methods

This architecture provides a solid foundation for directory management while maintaining the flexibility needed for different storage requirements across the application.