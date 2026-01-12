# Relay

**Lightweight PHP CMS for government content management**

Relay is a minimal, secure PHP-based content management system designed for government dispatch centers and organizations that need a simple, reliable way to manage content without the complexity of traditional CMS platforms.

## Features

- **Markdown-based content**: Write in simple Markdown with YAML frontmatter
- **Flexible content organization**: Supports both flat files and hierarchical directories with automatic fallback
- **Theme system**: Flexible HTML templates with PHP for custom layouts
- **No database required**: All data stored in JSON and Markdown files
- **Secure by default**: CSRF protection, rate limiting, secure password hashing
- **Multiple navigation menus**: Header, left sidebar, and right sidebar
- **Responsive layouts**: Three-column grid that adapts to content
- **Simple admin interface**: Easy menu editing with intuitive controls
- **File-based user management**: CLI tools for user administration
- **Flexible deployment**: Supports root or subdirectory deployment with auto-detection
- **Docker-ready**: Simple Docker setup for development and deployment

## Requirements

- PHP 8.1 or higher
- Apache with mod_rewrite (or nginx with equivalent configuration)
- Composer
- Docker (optional, for containerized deployment)

## Quick Start

### 1. Installation

```bash
# Clone the repository
git clone https://github.com/kevjava/relay.git
cd relay

# Install dependencies
composer install

# Initialize the system
php admin-tools.php init
```

During initialization, you'll be prompted to create an admin user.

### 2. Docker Setup

```bash
# Start the container
docker-compose up -d

# Visit your site
open http://localhost:8080
```

### 3. Login to Admin

Navigate to `http://localhost:8080/admin.php` and log in with the credentials you created during initialization.

## Content Management

### Creating Content

Content is created as Markdown files in the `/content` directory:

1. Create a new `.md` file (e.g., `content/my-page.md`)
2. Add frontmatter (optional) at the top
3. Write your content in Markdown
4. The page is immediately available at `/my-page`

**Example:**

```markdown
---
title: My Page Title
date: 2024-12-17
author: John Doe
---

# My Page

This is my content written in **Markdown**.

- Item 1
- Item 2
- Item 3
```

### Content Organization

Relay supports both flat and hierarchical content structures with automatic fallback, giving you flexibility in how you organize your content.

#### Flat Structure (Simple)

The traditional approach where each page is a single file:

```
content/
├── index.md         # Homepage at /
├── about.md         # About page at /about
├── contact.md       # Contact page at /contact
└── services.md      # Services page at /services
```

- `content/about.md` → `/about`
- `content/services.md` → `/services`

#### Hierarchical Structure (Organized)

Group related content in subdirectories using `index.md` files:

```
content/
├── index.md                    # Homepage at /
├── about/
│   ├── index.md               # About page at /about
│   ├── team.md                # Team page at /about/team
│   └── mission.md             # Mission page at /about/mission
└── docs/
    ├── index.md               # Docs home at /docs
    ├── getting-started.md     # Guide at /docs/getting-started
    └── api/
        └── index.md           # API docs at /docs/api
```

- `content/about/index.md` → `/about`
- `content/about/team.md` → `/about/team`
- `content/docs/api/index.md` → `/docs/api`

#### Mixed Structure (Flexible)

You can mix both approaches in the same site:

```
content/
├── index.md         # Homepage (flat)
├── about.md         # About page (flat)
├── docs/
│   ├── index.md    # Docs section (hierarchical)
│   └── guide.md
└── blog/
    └── index.md     # Blog section (hierarchical)
```

#### How Fallback Works

When you request a URL like `/about`, Relay tries to find the content in this order:

1. **Direct file first**: `content/about.md`
2. **Directory index second**: `content/about/index.md`
3. **404 if neither exists**

**Precedence Rule**: If both `content/about.md` AND `content/about/index.md` exist, the direct file takes precedence. This ensures backward compatibility and predictable behavior.

This means you can gradually migrate from flat to hierarchical structure, or choose the approach that best fits each section of your site.

### Best Practices

- Use descriptive filenames (lowercase, hyphens for spaces)
- Organize related content in subdirectories using `index.md` files
- Use flat structure for standalone pages (about, contact, etc.)
- Use hierarchical structure for content with multiple sub-pages (documentation, blog sections, etc.)
- Always include frontmatter with at least a title
- Keep file paths under 255 characters
- Use only alphanumeric characters, hyphens, and underscores

## Theme System

Relay includes a flexible theme system that allows you to create custom page layouts using HTML templates with PHP.

### Using Templates

Specify a template for any page by adding a `template` field to the frontmatter:

```markdown
---
title: My Blog Post
date: 2024-12-18
template: simple
---

Your content here...
```

If no template is specified, the `main` template is used by default.

### Built-in Templates

**main** (default):
- Three-column responsive layout
- Full-width header with navigation
- Optional left and right sidebars
- Adapts automatically based on which menus are configured

**simple**:
- Minimal single-column layout
- No sidebars or complex navigation
- Perfect for focused content pages

### Creating Custom Templates

1. Create a new `.php` file in the active theme's templates directory (e.g., `themes/default/templates/`):

```bash
touch themes/default/templates/my-template.php
chmod 644 themes/default/templates/my-template.php
```

2. Write your template using HTML with PHP blocks:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo url_base('/assets/css/relay.css'); ?>">
    <link rel="stylesheet" href="<?php echo url_base('/themes/default/css/default.css'); ?>">
</head>
<body>
    <h1><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
    <div><?php echo $content_html; ?></div>
</body>
</html>
```

3. Use it in your content by specifying `template: my-template` in the frontmatter

### Available Template Variables

Templates have access to:
- `$content_html` - Your rendered markdown content
- `$metadata` - All frontmatter fields
- `$title`, `$date`, `$author` - Common metadata fields
- `$page_title` - The page title
- `$header_menu`, `$left_menu`, `$right_menu` - Navigation menus
- `$current_path`, `$menu_current_path` - Current page path
- Helper functions: `menu_render()`, `menu_render_header()`, `url_base()`

**Important**: Always use `url_base()` for all URLs in templates to support subdirectory deployments:
```php
<link rel="stylesheet" href="<?php echo url_base('/assets/css/style.css'); ?>">
<a href="<?php echo url_base('/about'); ?>">About</a>
```

### Theme Selection

Relay supports multiple themes. The active theme is configured in `config/settings.json`:

```json
{
  "active_theme": "default"
}
```

Available themes are located in the `themes/` directory. Each theme contains:
- `templates/` - PHP template files
- `css/` - Theme-specific stylesheets
- `js/` - Theme-specific JavaScript
- `assets/` - Theme-specific images and other assets
- `theme.json` - Theme metadata

## Menu Management

### Editing Menus

1. Log in to `/admin.php`
2. Click on the menu you want to edit (header-menu, left-menu, right-menu)
3. Use the interface to:
   - **Add items**: Click "Add Item"
   - **Reorder**: Use ↑↓ buttons
   - **Nest items**: Use →← buttons to indent/outdent
   - **Delete**: Click "Delete" button
4. Click "Save Menu" when done

### Menu Types

- **Header Menu**: Horizontal navigation at the top
- **Left Menu**: Vertical sidebar navigation (supports nesting)
- **Right Menu**: Right sidebar (optional)

## User Management

User management is handled through CLI tools:

```bash
# Create a new user
php admin-tools.php create-user username role
# Roles: admin, editor

# Reset a user's password
php admin-tools.php reset-password username

# List all users
php admin-tools.php list-users

# Get help
php admin-tools.php help
```

### User Roles

- **admin**: Full access to all features
- **editor**: Can edit menus and change own password

## Security Considerations

### Built-in Security Features

- **CSRF protection**: All forms protected with CSRF tokens
- **Rate limiting**: Login attempts limited (5 per 15 minutes)
- **Secure password hashing**: Argon2ID (with BCrypt fallback)
- **Session security**: HTTP-only, strict same-site cookies
- **Path traversal protection**: Content paths validated and sanitized
- **Direct access protection**: Config and content directories blocked by .htaccess

### Recommended Practices

1. **Use HTTPS** in production (configure in Docker or reverse proxy)
2. **Change default admin password** immediately after setup
3. **Keep PHP updated** to the latest stable version
4. **Restrict file permissions**:
   - Directories: 755
   - Files: 644
   - Config files: 640 (if possible)
5. **Regular backups**: Back up `/content` and `/config` directories
6. **Monitor access logs** for suspicious activity
7. **Use strong passwords**: Minimum 12 characters, mixed case, numbers, symbols

## Deployment

### Subdirectory Deployment

Relay supports deployment to subdirectories (e.g., `/relay/`, `/cms/`, `/sites/my-cms/`) with automatic base path detection. No code changes are required.

#### For Root Deployment (`/`)

Deploy as normal - Relay automatically detects root deployment and works immediately.

#### For Subdirectory Deployment (e.g., `/relay/`)

1. **Upload files** to your subdirectory (e.g., `/var/www/html/relay/`)

2. **Edit `.htaccess`** - Uncomment and set the `RewriteBase` directive:

```apache
# Subdirectory Deployment Configuration
# If deploying to a subdirectory (e.g., /relay/ or /my-cms/), uncomment and set:
RewriteBase /relay/
```

3. **Done!** - All URLs automatically adjust to include the base path.

#### How It Works

- Base path is automatically detected from server environment
- All URLs (assets, links, forms, redirects) adjust automatically
- Menu configurations remain portable (stored without base path)
- Works in any subdirectory without code changes
- Backward compatible with root deployments

#### nginx Subdirectory Configuration

For nginx deployments in subdirectories, update your location block:

```nginx
location /relay/ {
    alias /var/www/html/relay/;
    try_files $uri $uri/ /relay/index.php?p=$uri&$args;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $request_filename;
        include fastcgi_params;
    }
}
```

### Deployment Checklist

- [ ] Change default admin credentials
- [ ] Configure HTTPS/SSL
- [ ] Set secure file permissions
- [ ] Configure proper session timeout
- [ ] Set up regular backups
- [ ] Test all .htaccess protections
- [ ] Review security headers
- [ ] Configure monitoring/logging
- [ ] Test password reset functionality
- [ ] Verify rate limiting is working
- [ ] If subdirectory deployment: Set RewriteBase in .htaccess

### Production Docker Setup

Edit `docker-compose.yml` for production:

```yaml
environment:
  - PHP_MEMORY_LIMIT=256M
  - HTTPS=on  # If using HTTPS
restart: always
```

### nginx Configuration

If using nginx instead of Apache:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/html;
    index index.php;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Block access to sensitive directories
    location ~ ^/(config|content)/ {
        deny all;
        return 403;
    }

    # Block sensitive files
    location ~ (composer\.(json|lock)|\.git.*|\.env) {
        deny all;
        return 403;
    }

    # PHP handling
    location / {
        try_files $uri $uri/ /index.php?p=$uri&$args;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## Troubleshooting

### Common Issues

**Problem**: White screen / 500 error
- Check PHP error logs
- Ensure vendor directory exists (run `composer install`)
- Verify file permissions
- Check PHP version (8.1+ required)

**Problem**: Cannot log in
- Verify users.json exists in config/
- Check that session directory is writable
- Clear browser cookies
- Try password reset via CLI

**Problem**: Content not displaying
- Check that .md file exists in content/
- Verify file permissions (must be readable)
- Check for PHP errors in content rendering
- Ensure Parsedown is installed

**Problem**: Menus not saving
- Check CSRF token is being sent
- Verify config/ directory is writable
- Check browser console for JavaScript errors
- Ensure admin user is logged in

**Problem**: 404 on all pages
- Verify .htaccess is being read (AllowOverride All)
- Check mod_rewrite is enabled
- For subdirectory deployment: Ensure RewriteBase is set in .htaccess
- For nginx: verify rewrite rules are configured

### Debug Mode

Enable debug mode by setting in `index.php` or `admin.php`:

```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

**WARNING**: Never enable debug mode in production!

## Development

### File Structure

```
/relay/
├── index.php              # Main router & theme
├── admin.php              # Admin interface
├── admin-tools.php        # CLI utilities
├── error-404.php          # 404 error page
├── composer.json          # Dependencies
├── docker-compose.yml     # Docker configuration
├── Dockerfile             # Docker image
├── .htaccess              # Apache configuration
├── claude.md              # Context for Claude Code sessions
├── CHANGELOG.md           # Version history
├── lib/                   # Core libraries
│   ├── auth.php           # Authentication
│   ├── content.php        # Content management
│   ├── menu.php           # Menu system
│   ├── csrf.php           # CSRF protection
│   ├── theme.php          # Template rendering
│   └── url.php            # URL helpers & base path detection
├── content/               # Markdown content files
├── config/                # JSON configuration
│   ├── users.json         # User credentials
│   ├── settings.json      # System settings (active theme, etc.)
│   └── *-menu.json        # Menu configurations
├── themes/                # Multi-theme system
│   ├── default/           # Default theme
│   │   ├── templates/    # PHP templates
│   │   │   ├── main.php # Three-column layout
│   │   │   └── simple.php # Minimal template
│   │   ├── css/          # Theme styles
│   │   │   └── default.css
│   │   ├── js/           # Theme scripts
│   │   └── assets/       # Theme assets
│   └── uswds/            # US Web Design System theme
│       ├── templates/
│       ├── css/
│       └── assets/
├── assets/                # Core CMS assets
│   ├── css/
│   │   ├── relay.css      # Core stylesheet (for admin/errors)
│   │   └── admin.css      # Admin stylesheet
│   ├── js/
│   │   └── menu-editor.js # Menu editor JS
│   └── img/
└── vendor/                # Composer dependencies
```

### Customization

**Templates**: Create custom templates in `themes/[theme-name]/templates/`. Each theme can have its own set of templates.

**Styles**:
- Core styles: `assets/css/relay.css` (minimal styles for admin/error pages)
- Theme styles: `themes/[theme-name]/css/` (theme-specific styling)

**Admin**: Customize `assets/css/admin.css` for admin interface styling.

**Functionality**: Extend core libraries in `lib/` or add custom functions to `lib/theme.php`.

**Creating a New Theme**:
1. Create a new directory in `themes/` (e.g., `themes/mytheme/`)
2. Add required structure: `templates/`, `css/`, `js/`, `assets/`
3. Create `theme.json` with metadata (name, version, templates list)
4. Create at least `templates/main.php`
5. Activate by setting `"active_theme": "mytheme"` in `config/settings.json`

## Contributing

Contributions are welcome! Please read SECURITY.md for security-related contributions.

## License

MIT License - see LICENSE file for details

## Support

For issues, questions, or contributions, please visit:
https://github.com/kevjava/relay/issues

---

**Relay** - Simple, secure content management that stays out of your way.
