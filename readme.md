# HelperBox Drupal Module

HelperBox is a comprehensive custom Drupal module that delivers a broad collection of features to support and extend site development. It includes helper functions, custom blocks, Views fields, field formatters, date/time widgets, APIs, theme template suggestions, preprocess hooks, form field access controls, and various utility hooks.

**Maintainer:** santoshtmp7@gmail.com
**Version:** 11.3.1
**Drupal Compatibility:** Drupal 11+

## Features Overview

### 1. Template Suggestions System
- Provides template suggestions for forms, paragraphs, fields, colorbox_view_mode_formatter, and other components
- Advanced suggestions for specific content types, nodes, and view contexts
- Custom theme implementations for various components

### 2. Custom Views Fields
- **Node Counter (`helperbox_count_node`)**: Counts nodes based on content type and conditions
- **Block Renderer (`helperbox_renderblock`)**: Renders blocks and views blocks within Views
- **Custom HTML Text (`helperbox_custom_text`)**: Displays full HTML content with token support
- **Media Adder (`helperbox_add_media`)**: Adds media to views with advanced options

### 3. Custom Field Formatters
- **Phone Number Formatter**: Formats text fields as clickable phone links
- **Media Information Formatter**: Displays media file information (size, type, extension, download links)
- **Date Range Formatter**: Custom date range formatting options

### 4. Advanced Date/Time Widgets
- **Custom Date Time Widget (`helperbox_date_time_widget`)**: Enhanced date/time input with flexible configurations
  - Multiple date order options (YMD, MDY, DMY, YM, MY, Y, M, YMopt)
  - Customizable year ranges (past/future)
  - Time type options (12/24 hour)
  - Optional month selection for year/month combinations

### 5. Custom Blocks
- **Banner Block**: Feature-rich banner with multiple layout options, media support, CTAs, and highlights
- **Contact Block**: Contact information with social links, webforms, and media
- **Content Block**: Dynamic content display with view options
- **Menu Block**: Custom menu rendering with social links
- **Repeated Content Block**: Reusable content sections

### 6. Media Handling Features
- Custom media thumbnail updates via `field_custom_thumbnail`
- Media library information helper with file details
- Image style integration
- File size conversion utilities

### 7. Form Field Access Controls
- Configurable field access based on content type and bundle
- Node-specific field access rules
- Form ID-based field access controls
- Maximum node limits per content type
- Field validation (title uniqueness, country code uniqueness)

### 8. Theme System Enhancements
- Custom theme implementations for various components
- Template suggestions for paragraphs based on context
- Field template suggestions
- View mode formatter suggestions

### 9. Utility Functions
- Error logging with backtrace
- Byte-to-size conversion utilities
- Content type retrieval helpers
- Block rendering utilities
- Media information extraction

### 10. API Integration
- Custom API endpoint at `/api/testhelperbox`
- JSON response capabilities
- Integration with Config Pages module

### 11. Preprocessing Hooks
- Node preprocessing with content-type-specific methods
- Paragraph preprocessing with view context
- Views field preprocessing
- Language block preprocessing

### 12. Configuration Management
- Settings form at `/admin/config/content/customhelperbox`
- Enable media custom thumbnail settings
- Custom field access rules configuration
- Node validation rules

## Installation

1. Place the helperbox module in your Drupal installation's modules directory
2. Enable the module via admin interface or Drush: `drush en helperbox`
3. Configure settings at `/admin/config/content/customhelperbox`

## Dependencies

- Drupal core modules: system, block, field, datetime
- Third-party modules: select2, media_library_form_element, menu_item_extras, config_pages, conditional_fields

## Configuration

The module provides extensive configuration options accessible at `/admin/config/content/customhelperbox` where you can:
- Enable/disable media custom thumbnail
- Configure field access rules
- Set maximum node limits per content type
- Manage validation rules

## Usage Examples

### Using Custom Views Fields
1. Create a new View or edit an existing one
2. Add a field and select from HelperBox custom fields
3. Configure the field options as needed

### Using Custom Blocks
1. Navigate to Structure > Block Layout
2. Add a HelperBox custom block
3. Configure the block settings

### Using Custom Field Formatters
1. Configure a field display on a content type
2. Select a HelperBox field formatter
3. Adjust formatter settings as needed

## Development Notes

This module implements numerous Drupal hooks and follows Drupal coding standards. It includes extensive error handling and logging capabilities.

## References
1. https://www.drupal.org/documentation
2. https://drupalize.me/
3. https://www.drupal.org/docs/creating-modules/creating-custom-blocks/create-a-custom-block-plugin
4. https://www.drupal.org/docs/user_guide/en/block-create-custom.html
5. https://www.drupal.org/project/media_library_form_element
6. https://drupal.stackexchange.com/questions/267317/how-can-i-use-a-media-field-in-a-custom-form
7. https://www.drupal.org/docs/drupal-apis/form-api/conditional-form-fields
8. https://www.drupal.org/docs/develop/drupal-apis/javascript-api/ajax-forms
9. https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21theme.api.php/function/hook_preprocess_HOOK/10
10. https://www.youtube.com/@WebWash 
11. https://drupalize.me/tutorial/concept-entity-queries

