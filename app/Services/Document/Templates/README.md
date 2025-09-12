# Document Templates & Translations Guide

This guide explains how to create and use Document Templates with full translation support including dot notation for nested values.

## Table of Contents

1. [Template Structure](#template-structure)
2. [Creating a New Template](#creating-a-new-template)
3. [Translation System](#translation-system)
4. [Using Translations](#using-translations)
5. [Dot Notation Support](#dot-notation-support)
6. [Best Practices](#best-practices)

## Template Structure

Every document template follows this directory structure:

```
app/Services/Document/Templates/
├── BaseDocumentTemplate.php           # Base class for all templates
├── YourTemplate/
│   ├── YourTemplate.php               # Main template class
│   └── Translations/
│       ├── En.php                     # English translations
│       ├── Ar.php                     # Arabic translations
│       └── ...                        # Additional language files
```

## Creating a New Template

### 1. Create the Template Class

```php
<?php

declare(strict_types=1);

namespace App\Services\Document\Templates\YourTemplate;

use App\Contracts\Document\DocumentTemplateTranslatorInterface;
use App\Services\Document\Templates\BaseDocumentTemplate;
use App\Services\Document\Templates\YourTemplate\Translations\{Ar, En};
use BumpCore\EditorPhp\Blocks\{Header, Paragraph};

final class YourTemplate extends BaseDocumentTemplate
{
    /**
     * Get template description for UI
     */
    public static function getDescription(): string
    {
        return app(DocumentTemplateTranslatorInterface::class)
            ->translateMeta('your_template', 'description');
    }

    /**
     * Get template name for UI
     */
    public static function getName(): string
    {
        return app(DocumentTemplateTranslatorInterface::class)
            ->translateMeta('your_template', 'name');
    }

    /**
     * Available translation classes
     */
    public static function getAvailableTranslations(): array
    {
        return [
            En::class,
            Ar::class,
        ];
    }

    /**
     * Template configuration
     */
    public function getConfigArray(): array
    {
        return [
            'setting1' => 'value1',
        ];
    }

    /**
     * Template data
     */
    public function getDataArray(): array
    {
        return [];
    }

    /**
     * Set the translator instance
     */
    public function setTranslator(DocumentTemplateTranslatorInterface $translator): static
    {
        $this->translator = $translator;
        return $this;
    }

    /**
     * Define the template blocks
     */
    public function getBlocks(): array
    {
        return [
            Header::make([
                'text' => $this->t('blocks.header.title'),
                'level' => 1,
            ]),
            Paragraph::make([
                'text' => $this->t('blocks.content.intro'),
            ]),
            // Add more blocks as needed
        ];
    }
}
```

### 2. Create Translation Files

#### English Translation (`Translations/En.php`)

```php
<?php

declare(strict_types=1);

namespace App\Services\Document\Templates\YourTemplate\Translations;

use App\Services\Document\Templates\Translation\BaseTranslation;

final class En extends BaseTranslation
{
    public static function getLocaleCode(): string
    {
        return 'en';
    }

    public function getTranslations(): array
    {
        return [
            'meta' => [
                'name' => 'Your Template',
                'description' => 'Description of your template',
            ],
            'blocks' => [
                'header' => [
                    'title' => 'Welcome to Your Template',
                    'subtitle' => 'Getting started guide',
                ],
                'content' => [
                    'intro' => 'This is the introduction paragraph.',
                    'body' => 'Main content goes here.',
                ],
                'footer' => [
                    'text' => 'Thank you for using our template.',
                ],
                // Flat keys (backward compatibility)
                'simple_text' => 'This is a simple translation',
            ],
        ];
    }
}
```

#### Arabic Translation (`Translations/Ar.php`)

```php
<?php

declare(strict_types=1);

namespace App\Services\Document\Templates\YourTemplate\Translations;

use App\Services\Document\Templates\Translation\BaseTranslation;

final class Ar extends BaseTranslation
{
    public static function getLocaleCode(): string
    {
        return 'ar';
    }

    public function getTranslations(): array
    {
        return [
            'meta' => [
                'name' => 'القالب الخاص بك',
                'description' => 'وصف القالب الخاص بك',
            ],
            'blocks' => [
                'header' => [
                    'title' => 'مرحباً بك في القالب الخاص بك',
                    'subtitle' => 'دليل البدء',
                ],
                'content' => [
                    'intro' => 'هذه هي فقرة المقدمة.',
                    'body' => 'المحتوى الرئيسي يأتي هنا.',
                ],
                'footer' => [
                    'text' => 'شكراً لك على استخدام قالبنا.',
                ],
                // Flat keys (backward compatibility)
                'simple_text' => 'هذه ترجمة بسيطة',
            ],
        ];
    }
}
```

## Translation System

The translation system supports both simple keys and nested dot notation access.

### Key Features:

- **Multi-language Support**: Add as many language files as needed
- **Caching**: Translations are cached for performance
- **Fallback**: Falls back to English if current locale is unavailable
- **Dot Notation**: Access nested array values using dot notation
- **Parameter Replacement**: Support for dynamic parameters in translations

## Using Translations

### The `t()` Method

The `t()` method is available in all template classes and supports both simple and dot notation keys:

```php
// Simple key access (backward compatibility)
$this->t('simple_text')

// Dot notation access
$this->t('blocks.header.title')
$this->t('blocks.content.intro')
$this->t('blocks.footer.text')

// With parameters
$this->t('blocks.welcome.message', ['name' => 'John'])
```

### Translation Structure

Organize your translations in a nested structure:

```php
'blocks' => [
    'section_name' => [
        'property' => 'value',
        'another_property' => 'another value',
    ],
    'another_section' => [
        'items' => [
            'item1' => 'First item',
            'item2' => 'Second item',
        ],
    ],
]
```

## Dot Notation Support

### Accessing Nested Values

Use dot notation to access deeply nested translation values:

```php
// Access: blocks.header.title
$headerTitle = $this->t('blocks.header.title');

// Access: blocks.content.sections.intro.text
$introText = $this->t('blocks.content.sections.intro.text');

// Access: meta.author.name
$authorName = $this->t('meta.author.name');
```

### Parameter Replacement

Support dynamic content with parameter replacement:

```php
// Translation file:
'blocks' => [
    'welcome' => [
        'message' => 'Hello :name, welcome to :app_name!',
    ],
]

// Usage:
$welcome = $this->t('blocks.welcome.message', [
    'name' => 'John',
    'app_name' => 'MyApp'
]);
// Result: "Hello John, welcome to MyApp!"
```

### Backward Compatibility

Simple keys continue to work without changes:

```php
// Old style (still works)
$text = $this->t('simple_key');

// New style (recommended)
$text = $this->t('blocks.section.key');
```

## Best Practices

### 1. Translation Organization

```php
'blocks' => [
    'headers' => [
        'main' => 'Main Title',
        'sub' => 'Subtitle',
    ],
    'content' => [
        'intro' => 'Introduction text',
        'body' => 'Main content',
        'conclusion' => 'Conclusion text',
    ],
    'forms' => [
        'labels' => [
            'name' => 'Name',
            'email' => 'Email',
        ],
        'placeholders' => [
            'name' => 'Enter your name',
            'email' => 'Enter your email',
        ],
    ],
]
```

### 2. Consistent Key Naming

- Use snake_case for keys
- Group related translations together
- Use descriptive names: `blocks.contact_form.submit_button` instead of `blocks.btn1`

### 3. Parameter Usage

```php
// Translation
'blocks' => [
    'user_info' => [
        'greeting' => 'Welcome back, :user_name!',
        'last_login' => 'Last login: :date at :time',
    ],
]

// Usage
$greeting = $this->t('blocks.user_info.greeting', ['user_name' => $user->name]);
$lastLogin = $this->t('blocks.user_info.last_login', [
    'date' => $loginDate,
    'time' => $loginTime,
]);
```

### 4. Template Registration

Register your template in the appropriate service provider:

```php
// In DocumentServiceProvider or similar
$templates = [
    'general' => General::class,
    'your_template' => YourTemplate::class,
];
```

### 5. Testing Translations

Test both languages and dot notation access:

```php
// Test different locales
app()->setLocale('en');
$template = YourTemplate::make();
$englishText = $template->testTranslation('blocks.header.title');

app()->setLocale('ar');
$template = YourTemplate::make();
$arabicText = $template->testTranslation('blocks.header.title');
```

## Examples

### Complete Template Example

Here's a complete example of a Marketing Brief template:

```php
public function getBlocks(): array
{
    return [
        Header::make([
            'text' => $this->t('blocks.header.main_title'),
            'level' => 1,
        ]),
        Paragraph::make([
            'text' => $this->t('blocks.intro.description'),
        ]),
        Header::make([
            'text' => $this->t('blocks.objectives.title'),
            'level' => 2,
        ]),
        Paragraph::make([
            'text' => $this->t('blocks.objectives.content'),
        ]),
        Header::make([
            'text' => $this->t('blocks.target_audience.title'),
            'level' => 2,
        ]),
        Paragraph::make([
            'text' => $this->t('blocks.target_audience.description'),
        ]),
    ];
}
```

### Translation File Structure

```php
'blocks' => [
    'header' => [
        'main_title' => 'Marketing Campaign Brief',
    ],
    'intro' => [
        'description' => 'This document outlines the key elements of our marketing campaign.',
    ],
    'objectives' => [
        'title' => 'Campaign Objectives',
        'content' => 'Define clear, measurable objectives for this campaign.',
    ],
    'target_audience' => [
        'title' => 'Target Audience',
        'description' => 'Describe the primary and secondary target audiences.',
    ],
]
```

## Conclusion

The Document Template system provides a powerful, flexible way to create multilingual document templates with support for both simple and complex nested translations. The dot notation support makes it easy to organize translations logically while maintaining backward compatibility with existing templates.