# Language Files Organization

This directory contains language files organized by features and components for better maintainability and scalability.

## Structure

### Feature-based Language Files
- `auth.php` - Authentication related translations (login, register, password reset, etc.)
- `document.php` - Document editor and management translations (including EditorJS)
- `flow.php` - Flow management and workflow translations
- `feedback.php` - Feedback system translations
- `roles.php` - Role and member management translations

### Common Language Files
- `common.php` - Shared translations used across multiple features
- `ui.php` - UI components and interface elements translations

### Legacy Files
- `app.php` - Legacy application translations (being gradually migrated)
- `filament.php` - Filament framework translations
- `filament-language-switch.php` - Language switcher component translations

## Usage Examples

### Using feature-specific translations:
```php
// Document editor
__('document.editor.save')                    // "Save"
__('document.editor.saving')                  // "Saving..."
__('document.editor.auto_save_tooltip')       // "Auto-save every 30 seconds when enabled"

// Authentication
__('auth.login.title')                        // "Sign In"
__('auth.messages.failed')                    // "These credentials do not match our records."

// Flows
__('flow.actions.create')                     // "Create Flow"
__('flow.stages.in_progress')                 // "In Progress"

// Roles
__('roles.actions.manage_members')            // "Manage Members"
__('roles.types.assignee')                    // "Assignee"
```

### Using common translations:
```php
// Common actions
__('common.actions.save')                     // "Save"
__('common.actions.cancel')                   // "Cancel"

// Common labels
__('common.labels.name')                      // "Name"
__('common.labels.email')                     // "Email"

// Common messages
__('common.messages.success')                 // "Success"
__('common.validation.required')              // "This field is required"
```

### Using UI component translations:
```php
// Language switcher
__('ui.components.language_switch.label')     // "Language"

// File upload
__('ui.components.file_upload.drag_drop')     // "Drag and drop files here or click to browse"
```

## Migration from app.php

The existing `app.php` file contained many translations that have been migrated to feature-specific files:

- **Navigation items** → `common.php` under `navigation`
- **Common actions** → `common.php` under `actions`
- **Flow stages** → `flow.php` under `stages`
- **Role types** → `roles.php` under `types` and `system_roles`
- **Feedback status/urgency** → `feedback.php` under `status` and `urgency`
- **File upload** → `ui.php` under `components.file_upload`
- **EditorJS translations** → `document.php` under various sections

## Guidelines

1. **Feature-first**: Place translations in feature-specific files when they belong to a specific component or feature
2. **Common second**: Use common.php for translations shared across multiple features
3. **Nested keys**: Use nested arrays to group related translations logically
4. **Descriptive keys**: Use clear, descriptive keys that indicate the context and purpose
5. **Consistent naming**: Follow consistent naming patterns across all language files

## Adding New Translations

When adding new translations:
1. First check if there's an appropriate feature-specific file
2. If not, consider if it belongs in common.php or ui.php
3. Only add to app.php if it's truly application-specific and doesn't fit elsewhere
4. Gradually migrate existing translations from legacy files to the new structure

## Adding New Languages

When adding a new language:
1. Create the language directory (e.g., `es/`, `fr/`)
2. Copy the structure from `en/` directory
3. Translate all keys while maintaining the same structure
4. Update the language switcher component to include the new language

## Document Component Localization

For the document component specifically, use these translations:

```php
// Save button
__('document.editor.save')                    // "Save"
__('document.editor.saving')                  // "Saving..."

// Auto-save
__('document.editor.auto_save')               // "Auto-save"  
__('document.editor.auto_save_tooltip')       // "Auto-save every 30 seconds when enabled"
__('document.editor.toggle_auto_save')        // "Toggle auto-save"
__('document.editor.enabled')                 // "enabled"
__('document.editor.disabled')                // "disabled"
```