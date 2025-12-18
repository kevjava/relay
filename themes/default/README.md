# Relay CMS Theme System

This directory contains the theme system for Relay CMS. Templates are plain HTML files with PHP blocks that can be specified via markdown frontmatter.

## Directory Structure

```
theme/
├── templates/          # HTML template files
│   ├── main.html      # Default template (three-column layout)
│   └── simple.html    # Minimal template example
├── css/               # Theme-specific stylesheets
├── js/                # Theme-specific JavaScript
└── assets/            # Theme-specific assets (images, fonts, etc.)
    └── images/
```

## Using Templates

### In Markdown Content

To specify a template for a page, add a `template` field to the frontmatter:

```markdown
---
title: My Blog Post
date: 2024-12-18
author: John Doe
template: simple
---

Your content here...
```

If no `template` field is specified, the `main` template is used by default.

### Template Files

Templates are HTML files located in `theme/templates/` with a `.html` extension. They can include PHP blocks for dynamic content.

**Example:**

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>
</head>
<body>
    <h1><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
    <div><?php echo $content_html; ?></div>
</body>
</html>
```

## Available Template Variables

All templates have access to the following variables:

### Content Variables
- `$metadata` - Array of all frontmatter fields from the markdown file
- `$content_html` - Rendered HTML from markdown content
- `$page_title` - Page title (from metadata or default)
- `$current_path` - Current content path (e.g., "about", "getting-started")

### Convenient Extractions
These are extracted from `$metadata` for easier access:
- `$title` - Page title (same as `$metadata['title']`)
- `$date` - Publication date (same as `$metadata['date']`)
- `$author` - Author name (same as `$metadata['author']`)

### Menu Variables
- `$header_menu` - Header menu array
- `$left_menu` - Left sidebar menu array
- `$right_menu` - Right sidebar menu array
- `$menu_current_path` - Current path for menu highlighting (e.g., "/about")

### Helper Functions
Templates have access to all PHP functions including:
- `menu_render($menu_data, $current_path, $depth)` - Render sidebar menu
- `menu_render_header($menu_data, $current_path)` - Render header menu
- `htmlspecialchars($string, ENT_QUOTES, 'UTF-8')` - Escape HTML
- `date($format)` - Format dates
- Any custom PHP functions you define

## Creating a New Template

### Step 1: Create Template File

Create a new `.html` file in `theme/templates/`:

```bash
touch theme/templates/my-template.html
chmod 644 theme/templates/my-template.html
```

### Step 2: Write Template HTML

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="/assets/css/relay.css">
</head>
<body>
    <header>
        <h1><a href="/">My Site</a></h1>
    </header>

    <main>
        <?php if (isset($title)): ?>
            <h1><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
        <?php endif; ?>

        <?php if (isset($date)): ?>
            <p>Published: <?php echo htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <div>
            <?php echo $content_html; ?>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> My Site</p>
    </footer>
</body>
</html>
```

### Step 3: Use in Content

Add `template: my-template` to your markdown frontmatter:

```markdown
---
title: Example Page
template: my-template
---

Page content here.
```

## Template Examples

### Accessing Custom Metadata

```html
<!-- In your markdown: -->
---
title: Project Showcase
hero_image: /assets/hero.jpg
category: Portfolio
---

<!-- In your template: -->
<?php if (isset($metadata['hero_image'])): ?>
    <img src="<?php echo htmlspecialchars($metadata['hero_image'], ENT_QUOTES, 'UTF-8'); ?>" alt="Hero">
<?php endif; ?>

<?php if (isset($metadata['category'])): ?>
    <span class="category"><?php echo htmlspecialchars($metadata['category'], ENT_QUOTES, 'UTF-8'); ?></span>
<?php endif; ?>
```

### Conditional Sidebar Display

```html
<div class="container">
    <?php if (!empty($left_menu)): ?>
        <aside class="sidebar">
            <?php echo menu_render($left_menu, $menu_current_path); ?>
        </aside>
    <?php endif; ?>

    <main class="content">
        <?php echo $content_html; ?>
    </main>
</div>
```

### Different Layouts by Content Type

```html
<?php
// Determine layout based on metadata
$layout = $metadata['layout'] ?? 'default';
?>

<?php if ($layout === 'wide'): ?>
    <div class="wide-layout">
        <?php echo $content_html; ?>
    </div>
<?php else: ?>
    <div class="standard-layout">
        <div class="constrained">
            <?php echo $content_html; ?>
        </div>
    </div>
<?php endif; ?>
```

## Built-in Templates

### main.html (Default)

Three-column responsive layout with:
- Full-width header with site title and horizontal menu
- Optional left sidebar for navigation
- Center content area
- Optional right sidebar for secondary navigation
- Full-width footer

**Grid system:**
- Three columns when both sidebars present: `250px | 1fr | 250px`
- Two columns with left sidebar: `250px | 1fr`
- Two columns with right sidebar: `1fr | 250px`
- Single column when no sidebars: `1fr`

**Usage:**
Default template - no frontmatter needed, or explicitly:
```markdown
---
template: main
---
```

### simple.html

Minimal layout with:
- Simple header with site name
- Single-column content area
- Basic footer
- No sidebars or navigation menus

**Usage:**
```markdown
---
template: simple
---
```

## Best Practices

### Security

1. **Always escape user content:**
   ```html
   <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>
   ```

2. **Content HTML is already safe:**
   Markdown is rendered by ParsedownExtra and can be output directly:
   ```html
   <?php echo $content_html; ?>
   ```

3. **Template names are sanitized:**
   Template names can only contain alphanumeric characters, dashes, and underscores. Path traversal attempts are blocked.

### Performance

1. **Keep templates simple:**
   Complex logic should be in PHP libraries, not templates.

2. **Avoid database queries:**
   All data should be prepared in `index.php` and passed as variables.

3. **Minimize HTTP requests:**
   Combine CSS/JS files when possible.

### Maintainability

1. **Use semantic HTML:**
   ```html
   <article>, <aside>, <nav>, <header>, <footer>
   ```

2. **Keep templates DRY:**
   Consider creating reusable template partials (future enhancement).

3. **Document custom metadata fields:**
   If your template expects specific frontmatter fields, document them.

## Troubleshooting

### Template not found

**Error:** "Template system error: main template not found"

**Solutions:**
- Verify file exists: `ls -la theme/templates/main.html`
- Check permissions: Should be `644` (rw-r--r--)
- Ensure filename matches exactly (case-sensitive)

### PHP errors in template

**Error:** Parse error or syntax error

**Solutions:**
- Check PHP syntax: `php -l theme/templates/your-template.html`
- Ensure all `<?php` tags are properly closed with `?>`
- Check for unmatched quotes or brackets

### Variables undefined

**Error:** Undefined variable in template

**Solutions:**
- Always check if variable is set before using:
  ```php
  <?php if (isset($variable)): ?>
      <?php echo $variable; ?>
  <?php endif; ?>
  ```
- Use null coalescing operator:
  ```php
  <?php echo $metadata['field'] ?? 'default'; ?>
  ```

### CSS/JS not loading

**Solutions:**
- Use absolute paths: `/assets/css/style.css`
- Or use relative paths from root: `href="/theme/css/theme.css"`
- Check file permissions: Should be `644`
- Check directory permissions: Should be `755`

## Advanced Topics

### Dynamic Grid Classes (see main.html)

```php
<?php
$has_left = !empty($left_menu);
$has_right = !empty($right_menu);
$grid_class = 'relay-grid';

if ($has_left && $has_right) {
    $grid_class .= ' three-column';
} elseif ($has_left) {
    $grid_class .= ' two-column-left';
} elseif ($has_right) {
    $grid_class .= ' two-column-right';
} else {
    $grid_class .= ' single-column';
}
?>
<div class="<?php echo $grid_class; ?>">
    <!-- Content -->
</div>
```

### Custom Helper Functions

You can define custom template helper functions in `lib/theme.php`:

```php
/**
 * Generate asset URL for theme files
 */
function theme_asset_url(string $path): string {
    return '/theme/' . ltrim($path, '/');
}
```

Then use in templates:

```html
<link rel="stylesheet" href="<?php echo theme_asset_url('css/custom.css'); ?>">
```

## File Permissions

Ensure correct permissions for theme files:

```bash
# Theme directories
chmod 755 theme theme/templates theme/css theme/js theme/assets

# Template files
chmod 644 theme/templates/*.html

# CSS/JS files
chmod 644 theme/css/*.css theme/js/*.js
```

## Contributing

When creating new templates:

1. Follow existing naming conventions (lowercase, hyphens)
2. Include proper HTML5 doctype and meta tags
3. Make templates responsive
4. Test with various content types
5. Document any custom metadata fields required
6. Ensure accessibility (semantic HTML, ARIA labels)
