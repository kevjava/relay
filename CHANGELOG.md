# Changelog

All notable changes to Relay CMS will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **Theme System** - Complete template system for custom layouts
  - New `lib/theme.php` library with template rendering functions
  - Template sanitization and validation to prevent security issues
  - Support for specifying templates via `template:` frontmatter field
  - Default template automatically used when not specified
  - `theme/templates/` directory for template storage
  - `theme/templates/main.html` - Default three-column layout template
  - `theme/templates/simple.html` - Minimal template example
  - Theme directory structure for CSS, JS, and assets
  - Template variables passed via `extract()` for clean PHP syntax
  - Full documentation in `theme/README.md`

- **Three-Column Layout** - Redesigned default template
  - Full-width header with site title and navigation
  - Optional left sidebar for main navigation menu
  - Center content column (flexible width)
  - Optional right sidebar for secondary navigation
  - Full-width footer
  - Dynamic grid system that adapts based on which sidebars are present
  - Responsive design (collapses to single column on mobile)

- **Template Variables** - Rich data available to templates
  - Content: `$metadata`, `$content_html`, `$page_title`, `$current_path`
  - Convenient extractions: `$title`, `$date`, `$author`
  - Menus: `$header_menu`, `$left_menu`, `$right_menu`, `$menu_current_path`
  - Access to all helper functions: `menu_render()`, `menu_render_header()`

- **Documentation**
  - `claude.md` - Context file for Claude Code sessions
  - `theme/README.md` - Comprehensive theme development guide
  - `CHANGELOG.md` - This file

### Changed
- **index.php** - Refactored to use theme system
  - Removed hardcoded HTML output (lines 64-135)
  - Added theme library inclusion
  - Added template variable preparation
  - Now uses `theme_render_template()` for output
  - Reduced from 135 lines to 87 lines

- **CSS Grid System** - More flexible layout system
  - Replaced `.has-right-sidebar` with semantic class names
  - Added `.three-column`, `.two-column-left`, `.two-column-right`, `.single-column`
  - Better support for different sidebar combinations
  - Cleaner, more maintainable CSS

- **Menu Editor Controls** - Improved button layout
  - Changed from vertical stacking to horizontal row
  - Updated `flex-direction: column` to `flex-direction: row` in admin.css
  - Buttons now display as: ↑ ↓ ← → (left to right)

### Fixed
- **Menu Editor AJAX Errors** - Fixed JSON parsing failures
  - Added output buffer management in `admin.php` (lines 75-78)
  - Prevents PHP warnings/notices from corrupting JSON responses
  - Added `ob_start()`, `ob_end_clean()`, and `ob_end_flush()` calls
  - Ensures clean JSON output for AJAX endpoints

- **Fetch Error Handling** - Better error detection
  - Added `response.ok` check in `menu-editor.js` before parsing JSON
  - Prevents "Unexpected token '<'" errors
  - Provides clearer error messages when requests fail
  - HTTP status errors now caught and displayed properly

- **File Permissions** - Fixed Apache read/write access
  - Theme files: Set to `644` (rw-r--r--)
  - Theme directories: Set to `755` (rwxr-xr-x)
  - Config JSON files: Set to `666` (rw-rw-rw-) for Apache write access
  - Prevents "Permission denied" errors when saving menus

- **PHP Compatibility** - Updated for PHP 8.1+
  - Changed `strpos()` to `str_starts_with()` in theme.php
  - Better compatibility with modern PHP versions
  - Reduced deprecation warnings

### Security
- **Template Path Validation** - Robust security measures
  - Template names sanitized with regex: `^[a-zA-Z0-9_-]+$`
  - No slashes allowed in template names (prevents path traversal)
  - `realpath()` verification ensures paths stay within `theme/templates/`
  - Follows same security patterns as content path sanitization

- **Output Buffer Management** - Prevents information disclosure
  - AJAX endpoints clear output buffers before sending responses
  - Prevents PHP warnings from leaking server information
  - Ensures only intended JSON responses are sent

## [Previous] - Before December 18, 2024

### Existing Features (Not Changed)
- File-based CMS with markdown content
- YAML frontmatter parsing
- Menu management system (header, left, right sidebars)
- User authentication with ARGON2ID password hashing
- CSRF protection on all forms
- Rate limiting on login attempts
- Session management with secure flags
- Admin interface for menu and user management
- CLI tools for user management
- ParsedownExtra for markdown rendering
- Responsive CSS framework
- Docker support

---

## Version History Notes

This changelog was started on December 18, 2024, documenting the theme system implementation. Previous versions of Relay CMS were not formally versioned. The theme system implementation represents a major architectural change and should be considered version 2.0.0 when officially released.

## Future Planned Enhancements

These features are under consideration but not yet implemented:

- Template inheritance system (parent/child templates)
- Template partials/includes for reusable components
- Theme configuration file (`theme/theme.json`)
- Multiple theme support with theme switching
- Content API for headless CMS usage
- Image upload management
- Content versioning (Git-based or file-based)
- Full-text search functionality
- Web-based markdown editor in admin interface
- Template helper functions library
- Template caching for performance
- Drag-and-drop menu reordering
