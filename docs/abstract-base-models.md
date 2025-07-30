# Abstract Base Model Protection

This system prevents direct instantiation and manipulation of abstract base models, ensuring that only concrete implementations can be used.

## Architecture

### Components

1. **BaseModelContract** (`App\Contracts\Model\BaseModelContract`)
   - Interface defining required methods for base models
   - Enforces implementation of type identification and validation

2. **IsBaseModel** (`App\Concerns\Model\IsBaseModel`)  
   - Trait that provides abstract model protection
   - Hooks into Eloquent events to prevent operations
   - Provides utility methods for working with concrete models

3. **AbstractBaseModelException** (`App\Exceptions\AbstractBaseModelException`)
   - Custom exception thrown when invalid operations are attempted
   - Provides clear error messages for different scenarios

## Usage

### Creating a Protected Abstract Base Model

```php
<?php

use App\Concerns\Model\IsBaseModel;
use App\Contracts\Model\BaseModelContract;
use Illuminate\Database\Eloquent\Model;

abstract class BaseProduct extends Model implements BaseModelContract
{
    use IsBaseModel;

    // Define concrete models that extend this base
    public static function getConcreteModels(): array
    {
        return [
            PhysicalProduct::class,
            DigitalProduct::class,
            ServiceProduct::class,
        ];
    }

    // Abstract method that concrete models must implement
    abstract public function getProductType(): string;

    // Required by BaseModelContract
    public function getModelType(): string
    {
        return $this->getProductType();
    }
}
```

### Creating Concrete Models

```php
<?php

final class PhysicalProduct extends BaseProduct
{
    protected $table = 'physical_products';

    public function getProductType(): string
    {
        return 'physical';
    }
}
```

## Protection Features

### Prevented Operations

The following operations are automatically blocked on abstract base models:

- **Creating**: `BaseProduct::create()` ❌
- **Updating**: `$baseModel->update()` ❌  
- **Saving**: `$baseModel->save()` ❌
- **Deleting**: `$baseModel->delete()` ❌
- **Force Deleting**: `$baseModel->forceDelete()` ❌
- **Restoring**: `$baseModel->restore()` ❌
- **Replicating**: `$baseModel->replicate()` ❌

### Allowed Operations

These operations work normally on concrete models:

- **Creating**: `PhysicalProduct::create()` ✅
- **Updating**: `$physicalProduct->update()` ✅
- **Saving**: `$physicalProduct->save()` ✅
- **Querying**: `PhysicalProduct::where()` ✅

## Error Handling

When invalid operations are attempted, clear exceptions are thrown:

```php
try {
    BaseFeedback::create(['content' => 'test']);
} catch (AbstractBaseModelException $e) {
    // "Cannot create instances of abstract base model [App\Models\BaseFeedback]. 
    //  Use a concrete implementation instead."
}
```

## Utility Methods

### Working with Concrete Models

```php
// Check if a class is a valid concrete implementation
BaseFeedback::isConcrete(VideoFeedback::class); // true
BaseFeedback::isConcrete(BaseFeedback::class);  // false

// Create instances of concrete models safely
$feedback = BaseFeedback::createConcrete(VideoFeedback::class, [
    'content' => 'Video feedback content',
    // ... other attributes
]);

// Get all instances across concrete models
$allFeedback = BaseFeedback::allConcrete();

// Find by ID across all concrete models
$feedback = BaseFeedback::findAcrossConcretes('feedback-id');

// Count instances across all concrete models
$totalCount = BaseFeedback::countAcrossConcretes();
```

### Model Type Identification

```php
$videoFeedback = new VideoFeedback();
$videoFeedback->getModelType(); // 'video'
$videoFeedback->getFeedbackType(); // 'video'
$videoFeedback->isAbstractModel(); // false
```

## Integration with Factory Pattern

The abstract base model protection works seamlessly with factory patterns:

```php
use App\Services\FeedbackFactory;

// Factory automatically routes to appropriate concrete model
$feedback = FeedbackFactory::create([
    'creator_id' => $user->id,
    'content' => 'Test feedback',
    'feedbackable_type' => Document::class,
    'feedbackable_id' => $document->id,
    'timestamp' => 45.5, // Indicates VideoFeedback
]);

// Returns VideoFeedback instance, not BaseFeedback
echo get_class($feedback); // App\Models\VideoFeedback
```

## Best Practices

### 1. Always Define Concrete Models List

```php
public static function getConcreteModels(): array
{
    return [
        ConcreteModelA::class,
        ConcreteModelB::class,
        // Keep this list updated when adding new concrete models
    ];
}
```

### 2. Implement Required Abstract Methods

```php
abstract class BaseModel extends Model implements BaseModelContract
{
    use IsBaseModel;
    
    // Always implement these in concrete classes
    abstract public function getSpecificType(): string;
    
    public function getModelType(): string
    {
        return $this->getSpecificType();
    }
}
```

### 3. Use Factory Patterns for Creation

Instead of directly instantiating models, use factory patterns:

```php
// ❌ Don't do this
$feedback = new VideoFeedback();

// ✅ Do this  
$feedback = FeedbackFactory::create($attributes);
```

### 4. Handle Exceptions Appropriately

```php
try {
    $model = SomeFactory::create($attributes);
} catch (AbstractBaseModelException $e) {
    Log::error('Invalid model operation', [
        'error' => $e->getMessage(),
        'attributes' => $attributes,
    ]);
    
    // Handle gracefully - maybe redirect with error message
    return back()->withError('Invalid operation attempted.');
}
```

## Testing

### Test Abstract Model Protection

```php
/** @test */
public function it_prevents_direct_creation_of_abstract_models(): void
{
    $this->expectException(AbstractBaseModelException::class);
    
    BaseModel::create(['attribute' => 'value']);
}

/** @test */
public function it_allows_concrete_model_creation(): void
{
    $model = ConcreteModel::create(['attribute' => 'value']);
    
    $this->assertInstanceOf(ConcreteModel::class, $model);
    $this->assertFalse($model->isAbstractModel());
}
```

## Benefits

1. **Type Safety**: Prevents accidental instantiation of abstract models
2. **Clear Error Messages**: Developers get helpful feedback when making mistakes  
3. **Runtime Protection**: Catches errors that might not be caught at compile time
4. **Factory Pattern Support**: Works seamlessly with factory patterns
5. **Utility Methods**: Provides helpful methods for working with model hierarchies

## Example: BaseFeedback Implementation

```php
abstract class BaseFeedback extends Model implements BaseModelContract
{
    use HasFactory, HasUlids, LivesInBusinessDB, IsBaseModel;

    // Common functionality for all feedback types
    public function resolve(User $resolver): self { /* ... */ }
    public function reject(User $resolver): self { /* ... */ }
    
    // Abstract method that concrete models must implement
    abstract public function getFeedbackType(): string;

    // Required by BaseModelContract
    public function getModelType(): string
    {
        return $this->getFeedbackType();
    }

    // List all concrete feedback models
    public static function getConcreteModels(): array
    {
        return [
            VideoFeedback::class,
            AudioFeedback::class,
            DocumentFeedback::class,
            DesignFeedback::class,
            GeneralFeedback::class,
        ];
    }
}
```

This ensures that developers must use specific feedback types like `VideoFeedback::create()` instead of accidentally trying to use `BaseFeedback::create()`.