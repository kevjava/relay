# Project Specification: Relay CMS

## Overview

Relay is a lightweight, file-based PHP CMS for small content sites, especially government and dispatch-center deployments that want simple operations, minimal dependencies, and no database. Content is stored as Markdown, configuration is stored as JSON, and page rendering is handled by a theme/template system.

This document describes the **current implemented state of the codebase**.

## Runtime and Dependencies

### Application Requirements

- **PHP**: 8.1 or newer
- **Web server**: Apache with `mod_rewrite` and `mod_headers`
- **Package management**: Composer
- **Storage model**: file-based only; no database

### Composer Dependencies

- `erusev/parsedown`
- `erusev/parsedown-extra`

### Container Setup

The repository includes both:

- `Dockerfile` based on `php:8.2-apache`
- `docker-compose.yml` exposing the app on port `8080`

The Docker image:

- enables Apache `rewrite` and `headers`
- installs Composer
- installs `libsodium`
- runs `composer install --no-dev --optimize-autoloader`

## Current Directory Structure

```text
/relay/
├── index.php                 # Frontend router
├── admin.php                 # Admin interface
├── admin-tools.php           # CLI administration commands
├── error-404.php             # Standalone 404 page
├── composer.json
├── composer.lock
├── Dockerfile
├── docker-compose.yml
├── README.md
├── SECURITY.md
├── .htaccess
├── assets/
│   ├── css/
│   │   ├── relay.css         # Shared/core styles
│   │   └── admin.css         # Admin-only styles
│   ├── js/
│   │   └── menu-editor.js    # Vanilla JS menu editor
│   └── img/
├── content/
│   ├── .htaccess
│   └── *.md / nested dirs    # Markdown content
├── config/
│   ├── .htaccess
│   ├── users.json
│   ├── header-menu.json
│   ├── left-menu.json
│   ├── right-menu.json
│   └── settings.json
├── lib/
│   ├── auth.php
│   ├── content.php
│   ├── csrf.php
│   ├── menu.php
│   ├── settings.php
│   ├── theme.php
│   └── url.php
└── themes/
    ├── default/
    │   ├── css/
    │   ├── templates/
    │   └── theme.json
    └── uswds/
        ├── css/
        ├── fonts/
        ├── img/
        ├── js/
        ├── lib/
        ├── templates/
        └── theme.json
```

## Architecture Summary

Relay is organized around these core systems:

1. **Authentication and sessions** for admin access
2. **Markdown content loading** with frontmatter parsing
3. **JSON-backed menu management**
4. **Theme-based template rendering**
5. **Base-path-aware URL generation** for root or subdirectory deployment

## Core Libraries

### 1. Authentication (`lib/auth.php`)

Relay supports file-based multi-user authentication backed by `config/users.json`.

#### Implemented behavior

- usernames must match `^[a-zA-Z0-9_]+$`
- passwords are hashed with `PASSWORD_ARGON2ID` when available, otherwise `PASSWORD_BCRYPT`
- session timeout is **30 minutes**
- failed login attempts are rate-limited to **5 attempts per 15 minutes per session**
- session ID is regenerated on successful login
- session cookies are configured with:
  - `HttpOnly`
  - `SameSite=Strict`
  - `Secure` when HTTPS is detected
  - `session.use_strict_mode=1`

#### Implemented functions

- `auth_init_session(): void`
- `auth_load_users(): array`
- `auth_save_users(array $users): bool`
- `auth_validate_username(string $username): bool`
- `auth_check_rate_limit(): bool`
- `auth_record_failed_attempt(): void`
- `auth_get_lockout_time(): int`
- `auth_login(string $username, string $password): bool`
- `auth_logout(bool $expired = false): void`
- `auth_check(): bool`
- `auth_get_user(): ?array`
- `auth_require_login(string $redirect_url = ''): void`
- `auth_is_admin(): bool`
- `auth_change_password(string $username, string $old_password, string $new_password): bool`
- `auth_hash_password(string $password): string|false`
- `auth_create_user(string $username, string $password, string $role = 'editor'): bool`
- `auth_reset_password(string $username, string $new_password): bool`
- `auth_set_flash_message(string $message, string $type = 'info'): void`
- `auth_get_flash_message(): ?array`

#### User data format

```json
{
  "admin": {
    "password_hash": "$argon2id$...",
    "role": "admin"
  },
  "editor1": {
    "password_hash": "$argon2id$...",
    "role": "editor"
  }
}
```

#### Notes

- The code stores `admin` and `editor` roles.
- Current role enforcement is minimal: `auth_is_admin()` is used for display logic, not broad authorization boundaries across the admin UI.

### 2. CSRF Protection (`lib/csrf.php`)

Relay uses a single session-backed CSRF token for forms and AJAX requests.

#### Implemented behavior

- CSRF token is generated with `random_bytes(32)`
- token is stored in session
- token expires after **2 hours**
- validation uses `hash_equals()`
- helper APIs exist for:
  - hidden form field rendering
  - meta tag rendering for JavaScript
  - detailed failure reasons and user-facing messages

#### Implemented functions

- `csrf_generate_token(): string`
- `csrf_validate_token(string $token): bool`
- `csrf_get_validation_failure_reason(string $token): string`
- `csrf_validate_token_detailed(string $token): array`
- `csrf_get_error_message(string $reason): string`
- `csrf_token_field(): string`
- `csrf_token_meta(): string`
- `csrf_get_token(): string`

### 3. Content System (`lib/content.php`)

Relay loads Markdown content from `content/`, extracts simple YAML-style frontmatter, and renders HTML with Parsedown Extra.

#### Implemented behavior

- requested paths are sanitized by:
  - removing null bytes
  - trimming leading/trailing slashes
  - rejecting `.` and `..`
  - allowing only `[a-zA-Z0-9_-]` in each path segment
- empty path resolves to `index`
- content lookup supports both flat and hierarchical structures
- direct file path has precedence over nested `index.md`
- frontmatter parser supports simple `key: value` lines
- Markdown is rendered with `ParsedownExtra`
- raw HTML in Markdown is currently **allowed**

#### Path resolution

- `/` -> `content/index.md`
- `/about` -> `content/about.md`, else `content/about/index.md`
- `/about/team` -> `content/about/team.md`, else `content/about/team/index.md`

#### Implemented functions

- `content_sanitize_path(string $path): string|false`
- `content_get_file_path(string $path): string|false`
- `content_exists(string $path): bool`
- `content_parse_frontmatter(string $markdown): array`
- `content_render_markdown(string $markdown): string`
- `content_load(string $path): array|false`
- `content_get_title(array $metadata, string $default = 'Relay'): string`
- `content_list_files(string $path = ''): array`

#### Content return shape

`content_load()` returns:

```php
[
    'metadata' => [...],
    'html' => '<p>...</p>',
]
```

#### Security note

The code explicitly leaves Parsedown safe mode disabled because content files are treated as trusted filesystem-managed content.

### 4. Menu System (`lib/menu.php`)

Menus are stored as JSON files in `config/` and rendered as nested navigation structures.

#### Implemented behavior

- menu file names are restricted to `^[a-zA-Z0-9_-]+$`
- menus are loaded from `config/{menu-name}.json`
- supported built-in menus are typically:
  - `header-menu`
  - `left-menu`
  - `right-menu`
- menu items require:
  - `label`
  - `url`
- menu items may include recursive `children`
- menu rendering marks active links for exact matches and descendant sections
- menu URLs are prefixed with `url_base()` during rendering

#### Implemented functions

- `menu_load(string $menu_name): array`
- `menu_save(string $menu_name, array $menu_data): bool`
- `menu_validate(array $menu_data): bool`
- `menu_is_active(string $url, string $current_path): bool`
- `menu_render(array $menu_data, string $current_path = '', int $depth = 0): string`
- `menu_render_header(array $menu_data, string $current_path = ''): string`
- `menu_list(): array`
- `menu_flatten_to_nested(array $flat_items): array`
- `menu_nested_to_flat(array $nested_items, int $indent = 0): array`

#### Menu JSON format

```json
[
  {
    "label": "Home",
    "url": "/"
  },
  {
    "label": "About",
    "url": "/about",
    "children": [
      {
        "label": "Mission",
        "url": "/about/mission"
      }
    ]
  }
]
```

### 5. Settings (`lib/settings.php`)

Relay stores site-wide settings in `config/settings.json`.

#### Implemented default settings

```json
{
  "active_theme": "default",
  "site_name": "Relay CMS",
  "timezone": "America/New_York"
}
```

#### Implemented functions

- `settings_load(): array`
- `settings_save(array $settings): bool`
- `settings_validate(array $settings): bool`
- `settings_get(string $key, mixed $default = null): mixed`
- `settings_set(string $key, mixed $value): bool`

### 6. Theme System (`lib/theme.php`)

Relay includes a multi-theme system. Themes live under `themes/`, expose metadata through `theme.json`, and render PHP templates from a theme-local `templates/` directory.

#### Implemented behavior

- active theme is read from `config/settings.json`
- invalid or missing theme names fall back to `default`
- template names are sanitized to `[a-zA-Z0-9_-]+`
- templates are PHP files with `.php` extension
- pages can choose a template via content frontmatter:
  - `template: main`
  - `template: simple`
- missing page-specific templates fall back to `main`
- theme libraries can override core behavior by shipping `themes/{theme}/lib/{library}.php`

#### Implemented functions

- `theme_get_active_dir(): string`
- `theme_sanitize_template_name(string $template): string|false`
- `theme_get_template_path(string $template): string|false`
- `theme_template_exists(string $template): bool`
- `theme_render_template(string $template, array $variables): void`
- `theme_list_available(): array`
- `theme_get_metadata(string $theme_name): array|false`
- `theme_validate(string $theme_name): bool`
- `theme_get_active(): string`
- `theme_set_active(string $theme_name): bool`
- `theme_load_lib(string $library): bool`

#### Implemented theme metadata requirements

Each theme must provide `theme.json` with at least:

- `name`
- `version`
- `templates`

#### Built-in themes

- `themes/default`
- `themes/uswds`

### 7. URL Helpers (`lib/url.php`)

Relay supports both root deployment and subdirectory deployment without hardcoding the base path.

#### Implemented behavior

- base path is detected from `$_SERVER['SCRIPT_NAME']`
- `url_base()` prefixes app URLs with the detected base path
- `url_strip_base_path()` removes the base path during routing

#### Implemented functions

- `url_get_base_path(): string`
- `url_base(string $path): string`
- `url_strip_base_path(string $full_path): string`

## Request Flow

### Frontend (`index.php`)

Current frontend flow:

1. load Composer and core libraries
2. load theme-specific `menu` override, if present
3. start the auth session
4. derive the request path from `$_GET['p']` or `REQUEST_URI`
5. strip the deployment base path if needed
6. sanitize the content path
7. load the matching Markdown file
8. load `header-menu`, `left-menu`, and `right-menu`
9. choose template from frontmatter or default to `main`
10. render with `theme_render_template()`

#### Template variables passed by `index.php`

- `$metadata`
- `$content_html`
- `$page_title`
- `$current_path`
- `$menu_current_path`
- `$header_menu`
- `$left_menu`
- `$right_menu`
- `$title`
- `$date`
- `$author`

### 404 Handling (`error-404.php`)

The 404 page is a standalone PHP view that:

- loads `lib/url.php`
- uses shared CSS from `assets/css/relay.css`
- shows Relay branding
- links back to the site root with `url_base('/')`

There is **no dedicated debug-mode implementation** in current code.

## Admin Interface (`admin.php`)

The admin interface supports login, dashboard actions, menu editing, and theme switching.

### Authentication model

- `action=login` is public
- all other admin actions require `auth_require_login()`

### Implemented POST actions

- `login`
- `change-password`
- `change-theme`
- `save-menu` (AJAX)

### Dashboard features

- menu list with links to edit each menu
- password change form
- user list display
- theme selection form
- site link and logout link

### Menu editor features

- add item
- inline edit of label and URL
- move item up/down
- indent/outdent to create nesting
- delete item with confirmation
- AJAX save
- unsaved-changes warning in the browser

### Current menu editor implementation details

- built with vanilla JavaScript in `assets/js/menu-editor.js`
- uses CSRF token from a `<meta>` tag
- uses a hidden `base-path` field for subdirectory-safe AJAX URLs
- converts flat indented rows into nested JSON before saving

### Not currently implemented in the admin UI

- drag-and-drop ordering
- parent-picker UI
- modal editing
- reset/cancel menu action

## CLI Tools (`admin-tools.php`)

Relay provides a CLI-only administration script.

### CLI guard

- exits unless `php_sapi_name() === 'cli'`

### Implemented commands

```bash
php admin-tools.php init
php admin-tools.php create-user <username> <role>
php admin-tools.php reset-password <username>
php admin-tools.php list-users
php admin-tools.php help
```

### `init` behavior

The initialization command currently:

- creates these directories if missing:
  - `lib`
  - `content`
  - `config`
  - `assets/css`
  - `assets/js`
  - `assets/img`
- creates:
  - `content/.htaccess`
  - `config/.htaccess`
- creates sample `content/index.md`
- creates `header-menu.json`, `left-menu.json`, and `right-menu.json`
- prompts for a default `admin` password
- creates the default admin user

### CLI banner

The script prints:

- `RELAY ADMINISTRATION TOOLS`
- `Lightweight PHP CMS`

## Themes and Templates

### Current template model

- templates are PHP files in `themes/{theme}/templates/`
- the frontend chooses a template from frontmatter or defaults to `main`
- templates are trusted PHP code, not sandboxed content
- theme-local libraries can override core functions before the core library is loaded

### Built-in template names

Current built-in themes each ship:

- `main`
- `simple`

### Theme selection

- the active theme is selected in `config/settings.json`
- the admin dashboard can change the active theme using `theme_set_active()`

## Apache and Web Security Configuration

### Root `.htaccess`

The current root `.htaccess` provides:

- rewrite rules that forward non-file, non-directory requests to `index.php?p=...`
- optional commented `RewriteBase` guidance for subdirectory deployments
- security headers:
  - `Content-Security-Policy`
  - `X-Frame-Options`
  - `X-Content-Type-Options`
  - `Referrer-Policy`
- directory listing disabled with `Options -Indexes`
- protection for sensitive files such as `composer.json`, `composer.lock`, `.git*`, and `.env`
- default UTF-8 charset
- PHP session hardening values when `mod_php` is present

### Protected directories

Both `content/.htaccess` and `config/.htaccess` contain:

```apache
Require all denied
```

## Security Characteristics

### Implemented protections

1. **Path traversal prevention**
   - content paths are sanitized
   - template names are sanitized
   - resolved content and template paths are checked against expected base directories

2. **CSRF protection**
   - all state-changing admin forms use CSRF tokens
   - AJAX menu saves require the same token

3. **Session hardening**
   - session ID regeneration on login
   - inactivity timeout
   - strict cookie flags

4. **Login throttling**
   - 5 failed attempts per 15 minutes per session

5. **Output safety**
   - most user-facing text is HTML-escaped before output
   - JSON responses are encoded with `json_encode()`

6. **Config/content directory protection**
   - Apache denies direct access

### Important trust assumptions

- Markdown content is treated as trusted input
- templates are trusted PHP code
- there is no web-based content editor or upload interface

## Current Documentation Set

The repository currently includes:

- `README.md`
- `SECURITY.md`
- `CHANGELOG.md`
- `CLAUDE.md`

## Known Limitations and Non-Goals in Current Code

- no database support
- no web-based Markdown content editor
- no media upload system
- no password reset email flow
- no fine-grained role/permission model beyond stored user roles
- no plugin system
- no theme marketplace
- no built-in content versioning
- no debug-mode error page toggle

## Functional Checklist for the Current Implementation

- [x] Login/logout
- [x] Password change for authenticated user
- [x] Session timeout handling
- [x] Login rate limiting
- [x] CSRF token generation and validation
- [x] Markdown rendering with frontmatter parsing
- [x] Flat and nested content routing
- [x] 404 page handling
- [x] Header/left/right menu loading
- [x] Nested menu save/render workflow
- [x] CLI user management
- [x] Theme selection via settings
- [x] Template selection via frontmatter
- [x] Subdirectory deployment support

## Branding

- **Project name**: Relay
- **Composer package**: `relay/relay`
- **Repository tagline**: `Lightweight PHP CMS for government content management`

## Summary

Relay is currently a file-based PHP CMS with:

- Markdown content and frontmatter
- JSON-backed menus and settings
- CLI-based user administration
- a multi-theme PHP template system
- subdirectory-aware routing and URL generation
- a small authenticated admin interface for menus, password changes, and theme selection
