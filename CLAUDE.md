# Claude Code Context - Relay CMS

This file provides context for Claude Code sessions to quickly understand the Relay CMS codebase.

## Project Overview

**Relay CMS** is a lightweight, file-based PHP content management system designed for government dispatch centers and organizations needing simple, secure content management without database complexity.

### Core Characteristics

- **File-based**: No database required - uses markdown files and JSON config
- **PHP 8.1+**: Modern PHP with strict typing
- **Security-focused**: Path traversal prevention, CSRF protection, rate limiting
- **Markdown-based**: Content authored in markdown with YAML frontmatter
- **Theme system**: HTML templates with PHP blocks (added Dec 2024)

## Project Structure

```
/relay/
├── index.php              # Main router - loads content and renders templates
├── admin.php              # Admin interface for menu/user management
├── admin-tools.php        # CLI tools for user management
├── error-404.php          # 404 error page
│
├── lib/                   # Core libraries
│   ├── auth.php          # Authentication, sessions, password hashing
│   ├── content.php       # Markdown parsing, frontmatter extraction
│   ├── menu.php          # Menu management (flat↔nested conversion)
│   ├── csrf.php          # CSRF token generation/validation
│   └── theme.php         # Template rendering system (NEW)
│
├── content/              # Markdown content files
│   └── *.md             # Pages with YAML frontmatter
│
├── config/               # JSON configuration
│   ├── users.json       # User credentials (hashed)
│   ├── header-menu.json # Top navigation
│   ├── left-menu.json   # Left sidebar navigation
│   └── right-menu.json  # Right sidebar navigation
│
├── themes/               # Theme system
│   ├── default/         # Default theme
│   │   ├── templates/  # PHP template files
│   │   │   ├── main.php    # Three-column layout
│   │   │   └── simple.php  # Minimal template
│   │   ├── css/
│   │   │   └── default.css # Theme-specific styles (4.6KB)
│   │   ├── js/
│   │   └── assets/
│   │
│   └── uswds/          # US Web Design System theme
│       ├── templates/
│       │   ├── main.php
│       │   └── simple.php
│       ├── css/
│       │   ├── uswds.min.css  # USWDS framework
│       │   └── theme.css      # Custom styles
│       └── assets/
│
└── assets/              # Core CMS assets
    ├── css/
    │   ├── relay.css   # Core styles for admin/error pages (1.5KB)
    │   └── admin.css   # Admin interface styles
    └── js/
        └── menu-editor.js # Menu editor with AJAX save
```

## Recent Major Changes (December 2024)

### Theme System Implementation

Added a complete multi-theme system allowing templates to be specified via markdown frontmatter.

**Key Files Added:**

- `lib/theme.php` - Template sanitization, validation, rendering, multi-theme support
- Multi-theme structure: `themes/default/` and `themes/uswds/`
- Template files with `.php` extension (changed from `.html`)
- `.htaccess` files in template directories for security

**Key Changes:**

- `index.php` - Refactored to use theme system instead of hardcoded HTML
- `assets/css/relay.css` - Split into minimal core styles (1.5KB) for admin/error pages
- Theme-specific CSS created for each theme (e.g., `themes/default/css/default.css`)
- Templates load both core CSS and theme CSS
- All template files use `.php` extension for better security and IDE support

### Three-Column Layout

Redesigned default template with clean, semantic structure:

1. Full-width header with site title
2. Left column for navigation (optional)
3. Center column for content
4. Right column for secondary navigation (optional)
5. Full-width footer

Dynamic grid classes based on which sidebars are present:

- `.three-column` - Both sidebars (250px | 1fr | 250px)
- `.two-column-left` - Left sidebar only (250px | 1fr)
- `.two-column-right` - Right sidebar only (1fr | 250px)
- `.single-column` - No sidebars (1fr)

### Bug Fixes

1. **Menu editor AJAX errors** - Added output buffer management in `admin.php` to prevent PHP warnings from corrupting JSON responses
2. **File permissions** - Set proper permissions on theme files (644) and directories (755)
3. **Config file permissions** - Changed config JSON files to 666 so Apache can write
4. **Fetch error handling** - Added `response.ok` check in menu-editor.js before parsing JSON

## Architecture Patterns

### Security Patterns

All user input is sanitized following these patterns:

1. **Path sanitization** (see `content_sanitize_path()` in content.php):
   - Whitelist: `^[a-zA-Z0-9/_-]+$`
   - Remove null bytes, trim whitespace
   - Reject `.` and `..`
   - No path traversal allowed

2. **Template name sanitization** (see `theme_sanitize_template_name()` in theme.php):
   - Whitelist: `^[a-zA-Z0-9_-]+$` (no slashes!)
   - Uses `realpath()` verification
   - Ensures resolved path is within `themes/[active-theme]/templates/`

3. **CSRF protection**:
   - All POST requests require valid CSRF token
   - Tokens stored in session with 2-hour expiration
   - Uses `hash_equals()` for timing-attack prevention

4. **Authentication**:
   - ARGON2ID password hashing (fallback to BCRYPT)
   - Rate limiting: 5 attempts per 15 minutes
   - Session timeout: 30 minutes
   - Secure session flags: HttpOnly, SameSite=Strict

### Code Conventions

1. **Function naming**: `prefix_verb_noun()` pattern
   - `content_load()`, `menu_render()`, `auth_login()`
   - Prefix indicates library: `content_`, `menu_`, `auth_`, `theme_`, `csrf_`

2. **HTML escaping**: Always use `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')`

3. **Constants**: Defined at top of each lib file
   - `RELAY_CONTENT_DIR`, `RELAY_THEMES_DIR`, `RELAY_MENU_DIR`, etc.

4. **No closing PHP tags**: Library files don't end with `?>` to prevent whitespace issues

5. **Type hints**: PHP 8.1+ strict types used throughout
   - Return types: `string|false`, `array|false`, `bool`, `void`

### Template System Details

**How it works:**

1. Markdown file has `template: simple` in frontmatter
2. `index.php` loads content, determines template (defaults to 'main')
3. Prepares variables array with content, metadata, menus
4. Calls `theme_render_template($template, $variables)`
5. Template validated, variables extracted with `extract()`
6. Template included with full PHP scope access

**Available template variables:**

- `$metadata` - All frontmatter fields
- `$content_html` - Rendered markdown
- `$page_title` - Page title
- `$title`, `$date`, `$author` - Convenient extractions
- `$header_menu`, `$left_menu`, `$right_menu` - Menu arrays
- `$current_path`, `$menu_current_path` - Path variables
- All PHP functions: `menu_render()`, `menu_render_header()`, etc.

**Security notes:**

- Templates are trusted code (like PHP files), not user input
- No sandboxing - templates need access to helper functions
- Template names heavily sanitized before file inclusion

## Common Operations

### Adding a new template

1. Create `themes/[theme-name]/templates/my-template.php`
2. Use PHP blocks for dynamic content: `<?php echo $content_html; ?>`
3. Have access to all template variables listed above
4. Set permissions: `chmod 644 themes/[theme-name]/templates/my-template.php`
5. Use in content: Add `template: my-template` to frontmatter

### Debugging menu save issues

1. Check browser console for JSON parse errors
2. Check PHP error logs: `tail -f /var/log/apache2/error.log`
3. Verify config file permissions: `ls -la config/*.json` (should be 666)
4. Check output buffer management in `admin.php` line 75-78
5. Verify AJAX request in menu-editor.js has `response.ok` check

### File permission issues

- **Theme files**: 644 (rw-r--r--)
- **Theme directories**: 755 (rwxr-xr-x)
- **Config JSON**: 666 (rw-rw-rw-) - Apache needs write access
- **Content markdown**: 644 (rw-r--r--)

## Known Quirks

1. **CSS architecture**: Split between core and theme-specific:
   - `assets/css/relay.css` - Minimal core styles for admin/error pages (~1.5KB)
   - Each theme provides its own complete CSS (e.g., `themes/default/css/default.css`)
   - Templates load both core CSS and theme CSS
   - Admin pages work standalone without any theme installed

2. **Template file extension**: Templates use `.php` extension, not `.html`
   - Better semantics for files containing PHP code
   - Improved IDE support and syntax highlighting
   - `.htaccess` in template directories blocks direct HTTP access

3. **Output buffering**: Menu save action in admin.php uses output buffer management to prevent PHP warnings from corrupting JSON. If adding new AJAX endpoints, follow the same pattern.

4. **Markdown parser**: Uses ParsedownExtra with HTML allowed (not escaped). Content is trusted.

5. **Menu nesting**: Menus use indentation-based nesting. The `menu.php` lib has `menu_flat_to_nested()` and `menu_nested_to_flat()` converters.

6. **Session handling**: `auth_init_session()` must be called before any output. Already done in index.php and admin.php.

## Critical Files Reference

### Entry Points

- **index.php** (87 lines) - Main router, loads content, renders templates
- **admin.php** (310 lines) - Admin dashboard, menu editor, password change
- **admin-tools.php** (313 lines) - CLI for user management

### Core Libraries

- **lib/theme.php** (299 lines) - Template rendering, multi-theme support
- **lib/content.php** (266 lines) - Markdown parsing, frontmatter
- **lib/menu.php** (296 lines) - Menu management, nested↔flat conversion
- **lib/auth.php** (433 lines) - Authentication, sessions, passwords
- **lib/csrf.php** (88 lines) - CSRF token protection
- **lib/url.php** (122 lines) - URL helpers, base path detection for subdirectory deployment

### Templates

- **themes/default/templates/main.php** - Default theme three-column layout
- **themes/default/templates/simple.php** - Default theme minimal template
- **themes/uswds/templates/main.php** - USWDS theme main layout
- **themes/uswds/templates/simple.php** - USWDS theme minimal template

### CSS Files

Core (shared):
- **assets/css/relay.css** (87 lines, 1.5KB) - Minimal core styles for admin/error pages
- **assets/css/admin.css** - Admin interface specific styles

Theme-specific:
- **themes/default/css/default.css** (261 lines, 4.6KB) - Default theme complete styles
- **themes/uswds/css/uswds.min.css** - USWDS framework styles
- **themes/uswds/css/theme.css** - USWDS theme custom styles

## Potential Future Enhancements

Ideas for future development:

### Already Implemented ✅
- ✅ **Multiple themes** - System supports `themes/` directory with multiple themes (default, uswds)
- ✅ **Theme configuration** - `theme.json` support exists in `lib/theme.php`
- ✅ **Template helper functions** - Functions defined in `lib/theme.php` (theme_get_active_dir, theme_get_metadata, etc.)
- ✅ **Separated CSS architecture** - Core vs theme-specific CSS split

### Not Yet Implemented
1. **Template inheritance** - Parent/child template system for sharing layouts
2. **Template partials/includes** - Reusable template components (header snippets, footer snippets)
3. **Theme switching UI** - Admin interface to change active theme (backend code exists)
4. **Content API** - JSON API for headless CMS usage
5. **Image uploads** - Upload management for markdown content with media library
6. **Content versioning** - Git-based or file-based versioning with rollback
7. **Search functionality** - Full-text search across content with indexing
8. **Admin content editor** - Web-based markdown editor with preview
9. **Template caching** - Cache compiled templates for performance
10. **Drag-and-drop menu editor** - More intuitive menu reordering interface

## Subdirectory Deployment Support

Relay supports deployment to subdirectories (e.g., `/relay/`, `/cms/`, `/sites/my-cms/`) with automatic base path detection.

### How It Works

**Auto-detection**: Base path is automatically detected using `dirname($_SERVER['SCRIPT_NAME'])`:
- Root deployment: `/index.php` → base path = `""` (empty)
- Subdirectory: `/relay/index.php` → base path = `"/relay"`

**URL Helper Functions** (`lib/url.php`):
- `url_get_base_path()` - Returns detected base path (cached)
- `url_base($path)` - Prefixes path with base path
- `url_strip_base_path($path)` - Removes base path from REQUEST_URI

### Usage in Code

**Templates**: Always use `url_base()` for URLs:
```php
<link rel="stylesheet" href="<?php echo url_base('/assets/css/relay.css'); ?>">
<a href="<?php echo url_base('/about'); ?>">About</a>
```

**PHP redirects**:
```php
header('Location: ' . url_base('/admin.php'));
```

**JavaScript**: BASE_PATH variable is injected by admin.php:
```javascript
fetch(BASE_PATH + "/admin.php?action=save-menu", { ... })
```

**Menu rendering**: URLs are stored in JSON without base path (e.g., `/about`), then prefixed automatically when rendered via `menu_render()` and `menu_render_header()`.

### Deployment Steps

**For subdirectory deployment**:
1. Upload files to subdirectory
2. Edit `.htaccess` and uncomment/set `RewriteBase`:
   ```apache
   RewriteBase /relay/
   ```
3. Done - all URLs adjust automatically

**For root deployment**: No changes needed, works immediately.

### Files Modified for Subdirectory Support

- `lib/url.php` - NEW: URL helper functions
- `index.php` - Routing strips base path
- `admin.php` - All URLs use url_base(), BASE_PATH injected
- `lib/auth.php` - Redirects use url_base()
- `lib/menu.php` - Menu rendering prefixes URLs
- `error-404.php` - All URLs use url_base()
- All 4 template files - Asset/navigation URLs use url_base()
- `assets/js/menu-editor.js` - AJAX uses BASE_PATH
- `.htaccess` - RewriteBase documentation added

## Development Context

### Last Session Summary (January 9, 2026)

- **Implemented subdirectory deployment support with auto-detection**
- Created `lib/url.php` with base path detection and URL helpers
- Updated all URLs across templates, admin, menus, and JavaScript
- Added `.htaccess` RewriteBase documentation
- Updated README.md with deployment instructions
- Backward compatible: root deployments unchanged
- Zero configuration required: automatic base path detection
- Menu JSON files remain portable (no base path in storage)

### Previous Session Summary (December 18, 2024)

- Implemented complete theme system with template support
- Refactored index.php to use templates instead of hardcoded HTML
- Created three-column layout with flexible sidebar system
- Fixed menu editor AJAX errors and permission issues
- Added proper error handling to fetch() requests
- Fixed CSS flex-direction for menu item controls (row instead of column)
- **Changed template extension from .html to .php** for better semantics and security
- **Split CSS architecture**: Core styles (1.5KB) vs theme-specific styles (4.6KB)
- Added `.htaccess` to template directories to block direct HTTP access
- Updated all themes (default, uswds) with new structure
- **Removed legacy single-theme support** - Only `themes/` directory is now supported
- Created comprehensive documentation (CLAUDE.md, CHANGELOG.md)

### Current State

- Multi-theme system fully functional (themes/default, themes/uswds)
- Template files use `.php` extension with security hardening
- CSS split: Core (`assets/css/relay.css`) for admin, theme CSS for frontend
- Admin interface works standalone without themes
- Two built-in themes (default, uswds) with extensible theme system
- Theme selection via `settings.json` with `active_theme` field
- **Subdirectory deployment support with automatic base path detection**
- File permissions configured correctly for Apache
- Complete documentation for developers and Claude Code sessions

### Technical Debt / Notes

- Template helper functions exist but could be expanded
- Consider adding template caching for performance
- Menu editor could benefit from drag-and-drop reordering
- Theme switching UI not yet built (backend support exists in lib/theme.php)
