# Laravel Validated DTO Documentation

## Overview

Laravel Validated DTO is a package for creating Data Transfer Objects (DTOs) with built-in validation in Laravel applications. It provides a robust solution for handling data validation and transformation across different contexts.

**Package:** `wendelladriel/laravel-validated-dto`

## Key Features

- Easily validate data when creating DTOs
- Define validation rules similar to Laravel Form Requests
- Support for typed properties
- Type casting for DTO properties
- Nested data support
- Custom type casters
- Livewire compatibility

## Why Use DTOs?

DTOs help transfer data between different parts of an application with a consistent format. The key advantage is defining validation once and reusing it across different contexts like:
- Web requests
- CLI commands
- Background jobs
- API responses

## Installation

```bash
composer require wendelladriel/laravel-validated-dto
```

## Generating DTOs

Create a new DTO using the Artisan command:

```bash
php artisan make:dto UserDTO
```

**Key Details:**
- DTOs are created in `app/DTOs` by default
- You can customize the namespace in `config/dto.php`
- Generated DTOs include core methods: `rules()`, `defaults()`, `casts()`

### Publishing Stubs

To customize DTO generation templates:

```bash
php artisan dto:stubs
```

## Defining DTO Properties

Properties are defined directly in the DTO class with proper typing:

```php
class UserDTO extends ValidatedDTO
{
    public string $name;
    public string $email;
    public string $password;
}
```

**Important:** Property types must be compatible with their defined Cast Type.

## Defining Validation Rules

### Method 1: Using `rules()` Method

```php
class UserDTO extends ValidatedDTO
{
    protected function rules(): array
    {
        return [
            'name'     => ['required', 'string'],
            'email'    => ['required', 'email'],
            'password' => [
                'required',
                Password::min(8)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ];
    }
}
```

### Method 2: Using `Rules` Attribute

For simpler cases, use the `Rules` attribute with the `EmptyRules` trait:

```php
class UserDTO extends ValidatedDTO
{
    use EmptyRules;

    #[Rules(['required', 'string', 'min:3', 'max:255'])]
    public string $name;

    #[Rules(
        rules: ['required', 'email', 'max:255'], 
        messages: ['email.email' => 'The given email is not a valid email address.']
    )]
    public string $email;
}
```

### Additional Validation

Implement an `after()` method for custom validation logic:

```php
protected function after(): array
{
    return [
        function (Validator $validator) {
            // Custom validation logic
            if ($this->someCondition()) {
                $validator->errors()->add('field', 'Custom error message');
            }
        }
    ];
}
```

## Type Casting

Transform DTO property types using two methods:

### Method 1: Define `casts()` Method

```php
protected function casts(): array
{
    return [
        'name' => new StringCast(),
        'age' => new IntegerCast(),
        'created_at' => new CarbonImmutableCast()
    ];
}
```

### Method 2: Use `Cast` Attribute

```php
#[Cast(BooleanCast::class)]
public bool $active;

#[Cast(type: ArrayCast::class, param: FloatCast::class)]
public ?array $grades;
```

### EmptyCasts Trait

If using attributes or no specific casting needs:

```php
class UserDTO extends ValidatedDTO
{
    use EmptyCasts;
    
    #[Cast(BooleanCast::class)]
    public bool $active;
}
```

## Main Capabilities

1. **Generate DTOs** with validation rules
2. **Define DTO properties** with proper typing
3. **Create DTO instances** with validation
4. **Access DTO data** in a structured way
5. **Set default values** for properties
6. **Transform data** with type casting
7. **Map properties** for data transformation
8. **Support simple and resource DTOs**

## Documentation Sections

### Getting Started
- Installation
- Configuration
- Upgrade Guide
- Changelog

### The Basics
- Generating DTOs
- Defining DTO Properties
- Defining Validation Rules
- Creating DTO Instances
- Accessing DTO Data
- Defining Default Values
- Transforming DTO Data
- Mapping DTO properties
- Simple DTOs
- Resource DTOs
- Wireable DTOs
- Lazy Validation
- Generating TypeScript Definitions

### Customize
- Custom Error Messages and Attributes
- Custom Exceptions

### Type Casting
- Introduction
- Available Types
- Create Your Own Type Cast
- Casting Eloquent Model properties to DTOs

## Use Cases

- **API Response Wrapping:** Transform and validate API responses
- **Input Validation:** Validate input data across different contexts
- **Validation Decoupling:** Separate validation logic from specific request types
- **Type-Safe Data Structures:** Create robust, typed data containers
- **Cross-Context Reusability:** Use same validation in web, CLI, and jobs

## Benefits

- **Consistency:** Define validation once, use everywhere
- **Type Safety:** Leverage PHP typing for robust data handling
- **Flexibility:** Support for multiple validation and casting approaches
- **Laravel Integration:** Seamless integration with Laravel ecosystem
- **Livewire Compatible:** Works with Livewire components
- **Maintainability:** Clean separation of concerns

---

*This documentation is based on Laravel Validated DTO v3+ and provides core concepts and examples for effective DTO usage in Laravel applications.*