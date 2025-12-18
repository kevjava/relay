# Relay

**Lightweight PHP CMS for government content management**

Relay is a minimal, secure PHP-based content management system designed for government dispatch centers and organizations that need a simple, reliable way to manage content without the complexity of traditional CMS platforms.

## Features

- **Markdown-based content**: Write in simple Markdown with YAML frontmatter
- **No database required**: All data stored in JSON and Markdown files
- **Secure by default**: CSRF protection, rate limiting, secure password hashing
- **Multiple navigation menus**: Header, left sidebar, and right sidebar
- **Simple admin interface**: Easy menu editing with drag-and-drop-like controls
- **File-based user management**: CLI tools for user administration
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
git clone https://github.com/yourorg/relay.git
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

- **Flat structure**: `content/about.md` → `/about`
- **Nested structure**: `content/docs/guide.md` → `/docs/guide`
- **Homepage**: `content/index.md` → `/`

### Best Practices

- Use descriptive filenames (lowercase, hyphens for spaces)
- Organize related content in subdirectories
- Always include frontmatter with at least a title
- Keep file paths under 255 characters
- Use only alphanumeric characters, hyphens, and underscores

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
├── lib/                   # Core libraries
│   ├── auth.php           # Authentication
│   ├── content.php        # Content management
│   ├── menu.php           # Menu system
│   └── csrf.php           # CSRF protection
├── content/               # Markdown content files
├── config/                # JSON configuration
│   ├── users.json         # User credentials
│   └── *-menu.json        # Menu configurations
├── assets/                # Public assets
│   ├── css/
│   │   ├── relay.css      # Main stylesheet
│   │   └── admin.css      # Admin stylesheet
│   ├── js/
│   │   └── menu-editor.js # Menu editor JS
│   └── img/
└── vendor/                # Composer dependencies
```

### Customization

**Theme**: Edit `index.php` to customize the HTML structure and layout.

**Styles**: Modify or extend `assets/css/relay.css` for custom styling.

**Admin**: Customize `assets/css/admin.css` for admin interface styling.

## Contributing

Contributions are welcome! Please read SECURITY.md for security-related contributions.

## License

MIT License - see LICENSE file for details

## Support

For issues, questions, or contributions, please visit:
https://github.com/yourorg/relay/issues

---

**Relay** - Simple, secure content management that stays out of your way.
