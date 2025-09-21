# EditorJS Block Creation Guide

This guide outlines the complete process for creating a new EditorJS block in this Laravel application, following the established patterns and conventions.

## Step-by-Step Implementation Process

### 1. Analyze Existing Code Structure
- Study existing block implementations (e.g., ObjectiveBlock)
- Review naming conventions and file organization
- Understand the project's architecture patterns
- Check existing block configurations and DTOs

### 2. Create Content Block Class (Backend PHP)
**File**: `app/Services/Document/ContentBlocks/{BlockName}Block.php`

Required components:
- Extend `BumpCore\EditorPhp\Block\Block`
- Implement validation rules with `rules()` method
- Add HTML purification with `allows()` method
- Create fake data generation with `static fake()` method
- Add data accessor methods (getName(), getValue(), etc.)
- Implement `isEmpty()` method for empty state detection
- Add `render()` and `renderRtl()` methods for HTML output
- Include utility methods for display formatting
- Add `getSummary()` method for analytics

### 3. Create Config Builder Class (Backend PHP)
**File**: `app/Services/Document/ConfigBuilder/Blocks/{BlockName}.php`

Required components:
- Implement `DocumentBlockConfigContract`
- Define `CLASS_NAME` constant matching the content block
- Set up default configuration array with validation rules
- Add fluent API methods for customization
- Include default tunes array with `['commentTune']`
- Implement `build()` method that returns the DTO
- Add any predefined data (like objectives/currencies) to config

### 4. Create DTO (Data Transfer Object)
**File**: `app/Services/Document/ConfigBuilder/Blocks/DTO/{BlockName}ConfigData.php`

Required components:
- Extend `SimpleDTO` (not ValidatedDTO to avoid casting issues)
- Define proper type declarations for all properties
- Keep `casts()` method simple to avoid validation conflicts
- Follow existing DTO patterns from other blocks

### 5. Create JavaScript EditorJS Plugin
**File**: `resources/js/components/editorjs/plugins/{block-name}-block.js`

Required components:
- Define `static get toolbox()` with title and icon
- Set `static get isReadOnlySupported()` to true
- Set `static get enableLineBreaks()` to true
- Define `static get tunes()` returning `['commentTune']`
- Implement constructor with data, config, api, readOnly, block parameters
- Add localization support with `initializeLocalization()`
- Create `render()` method that returns the wrapper element
- Implement `createPlaceholder()` for empty state
- Implement `createDisplay()` for filled state
- Add modal functionality with `createModal()` and form handling
- Include dark/light mode detection with `isDarkMode()`
- Add validation methods and error handling
- Implement `save()`, `validate()`, and `static get sanitize()` methods
- Add proper cleanup with `destroy()` method

### 6. Add RTL and Arabic Language Support
**Within the JavaScript plugin**:
- Add Arabic translations in `initializeLocalization()`
- Support RTL layout detection
- Include proper Arabic text in render methods
- Handle RTL-specific styling and layout

### 7. Register Block in All Builders
**Files to update**:
- `app/Services/Document/ConfigBuilder/Builders/Base.php`
- `app/Services/Document/ConfigBuilder/Builders/Simple.php`
- `app/Services/Document/ConfigBuilder/Builders/Advanced.php`
- `app/Services/Document/ConfigBuilder/Builders/Ultimate.php`

Add the block configuration:
```php
'{blockname}' => EditorConfigBuilder::{blockname}()->toArray(),
```

### 8. Add Block to Document Index
**File**: `resources/js/components/document/index.js`

Required updates:
- Import the block plugin
- Add to classMap with correct naming
- Add English and Arabic translations for the tool

Example:
```javascript
import BudgetBlock from '../editorjs/plugins/budget-block.js';

const classMap = {
    'BudgetBlock': BudgetBlock,
    // ... other blocks
};

const translations = {
    'en': {
        'BudgetBlock': 'Budget',
        // ... other translations
    },
    'ar': {
        'BudgetBlock': 'ميزانية',
        // ... other translations
    }
};
```

### 9. Test the Implementation

#### Backend Tests
Create test file: `tests/Feature/Services/Document/ConfigBuilder/Blocks/{BlockName}Test.php`
- Test default configuration
- Test customization methods
- Test fluent API chaining
- Test JSON output
- Verify commentTune inclusion
- Test validation rules

#### Frontend Testing
- Test modal functionality
- Verify dark/light mode support
- Test form validation
- Check save/load functionality
- Verify RTL support
- Test dropdown/selection features (if applicable)

## Key Patterns and Conventions

### Naming Conventions
- Content Block: `{Name}Block.php` (e.g., `BudgetBlock.php`)
- Config Builder: `{Name}.php` (e.g., `Budget.php`)
- DTO: `{Name}ConfigData.php` (e.g., `BudgetConfigData.php`)
- JavaScript: `{name}-block.js` (e.g., `budget-block.js`)
- Class name constant: Matches content block name

### CSS and Styling
- Use inline styles in JavaScript (Tailwind v4 compatibility)
- Support dark/light mode with zinc color palette
- Implement responsive design
- Follow existing spacing and typography patterns

### Validation and Error Handling
- Add proper form validation in both PHP and JavaScript
- Include user-friendly error messages
- Support multiple languages
- Handle edge cases (empty input, special characters, etc.)

### Performance Considerations
- Use debounced saving
- Implement proper cleanup in destroy() method
- Optimize for large numbers of blocks
- Cache predefined data when possible

## Common Gotchas and Solutions

1. **DTO Casting Issues**: Use SimpleDTO instead of ValidatedDTO
2. **CSS Not Loading**: Use inline styles instead of external CSS
3. **Dark Mode Detection**: Check `document.documentElement.classList.contains('dark')`
4. **Regex in Filtering**: Escape special characters to prevent regex errors
5. **Modal Positioning**: Use fixed positioning with proper z-index
6. **Event Cleanup**: Always remove event listeners in destroy() method

## File Checklist

Before considering the block complete, ensure all these files are created/updated:

- [ ] Content block class
- [ ] Config builder class  
- [ ] DTO class
- [ ] JavaScript plugin
- [ ] All builder registrations (4 files)
- [ ] Document index.js updates
- [ ] Test file creation
- [ ] Build process validation

## Build and Deploy

1. Run `npm run build` to compile JavaScript assets
2. Run tests to ensure functionality: `php artisan test --filter={BlockName}`
3. Verify in browser with different modes (light/dark, LTR/RTL)
4. Test across different builders (Base, Simple, Advanced, Ultimate)

---

**Note**: This guide is based on the ObjectiveBlock implementation and should be followed precisely to maintain consistency across the codebase.