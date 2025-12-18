# Project Specification: Relay - Lightweight PHP CMS

## Overview

Relay is a minimal, secure PHP-based content management system designed for government dispatch centers. The system uses Markdown files for content, JSON for configuration/menus, and includes a simple admin interface for menu management and user authentication.

## Technical Requirements

### Environment

- **Language**: PHP 8.1+
- **Web Server**: Apache with mod_rewrite (nginx compatibility documented but not required for MVP)
- **Dependencies**: Managed via Composer
  - `erusev/parsedown`
  - `erusev/parsedown-extra`
- **No database required**: All data stored in JSON files and Markdown

### Docker Setup

Create a `docker-compose.yml` with:

- PHP 8.1+ with Apache
- Composer pre-installed
- Volume mounts for development
- Port mapping (8080:80 or similar)

Include a `Dockerfile` if custom configuration needed.

## Directory Structure

```
/relay/
├── index.php              # Main router & theme
├── admin.php              # Admin interface
├── admin-tools.php        # CLI utilities (password reset, user creation)
├── composer.json          # Dependencies
├── composer.lock
├── vendor/                # Composer dependencies (gitignored)
├── lib/
│   ├── auth.php           # Authentication & session management
│   ├── content.php        # Markdown parsing & file operations
│   ├── menu.php           # Menu rendering functions
│   └── csrf.php           # CSRF token generation/validation
├── content/               # Markdown content files
│   ├── .htaccess          # Deny direct access
│   ├── index.md           # Homepage content
│   └── [subdirectories]/  # Organized content
├── config/                # JSON configuration files
│   ├── .htaccess          # Deny direct access
│   ├── users.json         # User credentials
│   ├── header-menu.json   # Header navigation
│   ├── left-menu.json     # Left sidebar navigation
│   └── right-menu.json    # Right sidebar navigation
├── assets/                # Public static files
│   ├── css/
│   ├── js/
│   └── img/
├── .htaccess              # Apache rewrite rules
├── error-404.php          # 404 error page
└── README.md              # Setup & usage documentation
```

## Core Functionality

### 1. Authentication System (`lib/auth.php`)

**Requirements:**

- Multi-user support with username/password authentication
- Password hashing using `password_hash()` with `PASSWORD_ARGON2ID` (fallback to `PASSWORD_BCRYPT`)
- Two roles: `admin` and `editor`
- PHP session-based authentication with secure flags
- Session timeout (configurable, default 30 minutes)
- Functions:
  - `auth_login($username, $password)`: Authenticate user, return bool
  - `auth_logout()`: Destroy session
  - `auth_check()`: Verify current session is valid
  - `auth_get_user()`: Get current user data (username, role)
  - `auth_require_login()`: Redirect to login if not authenticated
  - `auth_is_admin()`: Check if current user has admin role
  - `auth_change_password($username, $old_password, $new_password)`: Change password with validation

**User JSON Format** (`config/users.json`):

```json
{
  "admin": {
    "password_hash": "$argon2id$v=19$m=65536,t=4,p=1$...",
    "role": "admin"
  },
  "manager1": {
    "password_hash": "$argon2id$v=19$m=65536,t=4,p=1$...",
    "role": "editor"
  }
}
```

**Security Considerations:**

- Rate limiting on login attempts (track in session, 5 attempts per 15 minutes)
- Secure session configuration: httponly, secure (if HTTPS), samesite=strict
- Validate username format (alphanumeric + underscore only)

### 2. CSRF Protection (`lib/csrf.php`)

**Requirements:**

- Token generation and validation
- Store tokens in session
- Functions:
  - `csrf_generate_token()`: Create and store token, return token string
  - `csrf_validate_token($token)`: Verify token matches session, return bool
  - `csrf_token_field()`: Return HTML hidden input field
  - `csrf_token_meta()`: Return HTML meta tag for AJAX requests

**Usage Pattern:**

```php
// In form:
echo csrf_token_field();

// On submission:
if (!csrf_validate_token($_POST['csrf_token'])) {
    die('Invalid CSRF token');
}
```

### 3. Content System (`lib/content.php`)

**Requirements:**

- Load and parse Markdown files from `/content/` directory
- Support frontmatter (YAML-style) extraction
- Path traversal attack prevention
- Directory traversal support for nested content
- Functions:
  - `content_load($path)`: Load content file, return array with 'metadata' and 'html'
  - `content_exists($path)`: Check if content file exists
  - `content_sanitize_path($path)`: Clean and validate path
  - `content_parse_frontmatter($markdown)`: Extract YAML frontmatter, return array
  - `content_render_markdown($markdown)`: Convert Markdown to HTML using Parsedown Extra

**Frontmatter Format:**

```markdown
---
title: Page Title
date: 2024-12-17
author: Kevin
---

# Content starts here
```

**Path Mapping:**

- `/relay/` → `/content/index.md`
- `/relay/about` → `/content/about.md`
- `/relay/section/page` → `/content/section/page.md`

**Security:**

- Strip `..`, null bytes, absolute paths from requested paths
- Validate resolved path is within `/content/` directory
- Return 404 for invalid paths

### 4. Menu System (`lib/menu.php`)

**Requirements:**

- Load menu data from JSON files
- Render nested menus
- Support multiple independent menus
- Functions:
  - `menu_load($menu_name)`: Load menu JSON file, return array
  - `menu_save($menu_name, $menu_data)`: Save menu array to JSON
  - `menu_render($menu_data, $current_path)`: Generate HTML, mark active items
  - `menu_validate($menu_data)`: Validate menu structure

**Menu JSON Format:**

```json
[
  {
    "label": "Home",
    "url": "/home"
  },
  {
    "label": "About",
    "url": "/about",
    "children": [
      {
        "label": "Mission",
        "url": "/about/mission"
      },
      {
        "label": "Staff",
        "url": "/about/staff"
      }
    ]
  }
]
```

**Rendering:**

- Generate nested `<ul>` elements
- Mark current page as active
- Support unlimited nesting depth

### 5. Main Router (`index.php`)

**Requirements:**

- Parse incoming URL
- Route to appropriate content file
- Handle 404 errors
- Render page with theme
- Integrate all menu locations (header, left, right)

**Flow:**

1. Start session
2. Parse URL from `$_GET['p']` or `$_SERVER['REQUEST_URI']`
3. Sanitize path
4. Check if content exists
5. If exists: load content, parse frontmatter, render with theme
6. If not: show 404 page

**Theme Structure:**

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- CSS, meta tags -->
    <title>Relay - <?php echo htmlspecialchars($page_title); ?></title>
</head>
<body>
    <?php echo header($page_title, ['header' => $header_menu]); ?>

    <main>
        <div class="grid-container">
            <div class="grid-row">
                <?php if ($left_menu): ?>
                <aside class="tablet:grid-col-3">
                    <?php echo menu_render($left_menu, $current_path); ?>
                </aside>
                <?php endif; ?>

                <div class="tablet:grid-col-<?php echo $left_menu ? '9' : '12'; ?>">
                    <?php echo $content_html; ?>
                </div>
            </div>
        </div>
    </main>

    <?php echo footer(); ?>
</body>
</html>
```

### 6. Admin Interface (`admin.php`)

**Requirements:**

- Login page (if not authenticated)
- Menu editor interface
- Password change form
- User list (read-only display)
- All forms protected with CSRF tokens

**Pages/Views:**

**Login Page:**

- Username/password form
- Error messages for failed attempts
- Rate limiting display
- "Relay Admin" branding

**Dashboard (authenticated):**

- Welcome message with Relay branding
- List all editable menus
- Link to each menu editor
- Password change form
- User list (username and role only)
- Logout button

**Menu Editor:**

- Display current menu structure
- Add new menu item (label, URL, parent selection)
- Edit existing item (inline or modal)
- Delete item (with confirmation)
- Reorder items (up/down buttons or drag-and-drop with vanilla JS)
- Indent/dedent to nest items
- Save button (AJAX submission with CSRF token)
- Cancel/reset button

**JavaScript Requirements (vanilla):**

- Handle menu item reordering
- Add/edit/delete operations
- Indent/dedent functionality
- AJAX save with CSRF token in header
- Success/error messaging
- No external dependencies

### 7. CLI Tools (`admin-tools.php`)

**Requirements:**

- Command-line script for administrative tasks
- Must be run from command line only (check `php_sapi_name() === 'cli'`)
- Display "Relay Administration Tools" banner

**Commands:**

```bash
# Reset user password
php admin-tools.php reset-password <username>

# Create new user
php admin-tools.php create-user <username> <role>

# List all users
php admin-tools.php list-users

# Initialize fresh Relay installation
php admin-tools.php init
```

**Init Command:**

- Create directory structure
- Generate `.htaccess` files
- Create default admin user (prompt for password)
- Create sample content files
- Create empty menu JSON files
- Display success message with next steps

### 8. Apache Configuration (`.htaccess`)

**Root `.htaccess`:**

```apache
RewriteEngine On

# Redirect to index.php with path parameter
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?p=$1 [L,QSA]

# Security headers
Header set X-Frame-Options "SAMEORIGIN"
Header set X-Content-Type-Options "nosniff"
Header set X-XSS-Protection "1; mode=block"
```

**`content/.htaccess` and `config/.htaccess`:**

```apache
Require all denied
```

### 9. Error Pages

**`error-404.php`:**

- Standalone PHP file
- Styled 404 page
- Include basic header/footer with Relay branding
- No authentication required
- Link back to homepage

**Debug Mode:**

- Check for `DEBUG` constant or environment variable
- If enabled, show detailed error messages
- If disabled, show generic errors

## Security Requirements

1. **Input Validation:**
   - Sanitize all user inputs
   - Validate file paths
   - Whitelist allowed characters in usernames

2. **Output Encoding:**
   - HTML-escape all user-generated content
   - Use appropriate encoding for JSON responses

3. **Session Security:**
   - Regenerate session ID on login
   - Set secure session parameters
   - Implement session timeout

4. **File System:**
   - Prevent directory traversal
   - Validate file extensions
   - Restrict access to config/content directories

5. **Authentication:**
   - Rate limit login attempts
   - Strong password hashing
   - Secure password reset mechanism

## Testing Checklist

- [ ] Login/logout functionality
- [ ] Password change
- [ ] Menu CRUD operations (Create, Read, Update, Delete)
- [ ] Menu nesting/indenting
- [ ] Content rendering from Markdown
- [ ] Frontmatter parsing
- [ ] URL routing (flat and nested)
- [ ] 404 handling
- [ ] Path traversal attack prevention
- [ ] CSRF token validation
- [ ] Session timeout
- [ ] Login rate limiting
- [ ] CLI tools functionality
- [ ] `.htaccess` protection for config/content
- [ ] Multiple menu rendering (header, left, right)

## Documentation Requirements

**README.md should include:**

1. **Introduction**: What is Relay and who it's for
2. Installation instructions
3. Docker setup and usage
4. Initial configuration (running init command)
5. User management
6. Content organization best practices
7. Menu editing guide
8. Security considerations
9. Deployment checklist
10. nginx configuration alternative
11. Troubleshooting common issues
12. **Tagline**: "Relay - Lightweight PHP CMS for government content management"

**Additional Documentation:**

- `SECURITY.md`: Security best practices and considerations
- `CONTRIBUTING.md`: Guidelines for future contributions (if open-sourced)
- Inline code comments explaining security decisions

## Non-Requirements (Out of Scope for MVP)

- Database support
- Content editing through web interface (users sync via WebDAV/SSH)
- Image upload interface
- OAuth/SSO integration
- Multi-site management
- Content versioning (users manage via Git)
- Email notifications
- Advanced user permissions
- Plugin system
- Theme marketplace

## Success Criteria

Relay is considered complete when:

1. An admin can log in and edit menus without technical knowledge
2. Content creators can add Markdown files via file sync and see them rendered
3. All security requirements are met
4. The system passes all tests in the testing checklist
5. Documentation is complete and accurate
6. Docker container runs successfully with no configuration
7. The system can be deployed to multiple dispatch centers independently

## Project Branding

**Name:** Relay

**Tagline Options:**

- "Lightweight PHP CMS for content management"
- "Simple, secure content management"
- "Content management that stays out of your way"

**Usage in Code:**

- Project folder: `relay`
- Composer package: `relay/relay` or `yourorg/relay`
- Docker image: `relay-cms`
- Constants: `RELAY_VERSION`, `RELAY_ROOT`, etc.

**Style Guide:**

- Always capitalize "Relay" when referring to the project
- Use "a Relay installation" or "Relay sites" in prose
- Admin interface should display "Relay Admin" or "Relay Administration"

---

**Additional Notes:**

- Prioritize security and simplicity over features
- Code should be well-commented for future maintainers
- Follow PSR-12 coding standards where practical
- Design with the principle that Relay should be invisible to content creators and obvious to administrators
- Keep dependencies minimal
- Optimize for easy deployment across multiple dispatch centers
