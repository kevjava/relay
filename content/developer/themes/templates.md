---
title: Theme Templates
---

Templates are PHP files under the active theme's `templates/` directory. They receive a fixed set of variables from Relay and produce the complete HTML response.

## Select a Template

Content selects a template by name in its frontmatter:

```markdown
---
title: Annual Report
template: landing
---
```

This selects `themes/my-theme/templates/landing.php`. Template names may contain only letters, numbers, hyphens, and underscores. Do not include a path or `.php` extension.

When `template` is absent or empty, Relay uses `main`. If a requested template does not exist, Relay logs an error and tries `main`. If `main.php` is also unavailable, Relay returns HTTP 500 and stops rendering.

## Template Data Contract

`index.php` currently passes these variables:

| Variable | Expected value | Notes |
| --- | --- | --- |
| `$metadata` | `array<string, mixed>` | All frontmatter fields |
| `$content_html` | `string` | Rendered Markdown HTML; output intentionally as HTML |
| `$page_title` | `string` | Resolved title, with `Relay` as the fallback |
| `$current_path` | `string` | Sanitized content path without a leading slash |
| `$menu_current_path` | `string` | Current path with a leading slash |
| `$header_menu` | `array` | Header menu data or an empty array |
| `$left_menu` | `array` | Left menu data or an empty array |
| `$right_menu` | `array` | Right menu data or an empty array |
| `$title` | `?string` | Convenience value from `$metadata['title']` |
| `$date` | `?string` | Convenience value from `$metadata['date']` |
| `$author` | `?string` | Convenience value from `$metadata['author']` |

Declare expected variables at the top of templates to improve static analysis and editor completion:

```php
<?php
/** @var array<string, mixed> $metadata */
/** @var string $content_html */
/** @var string $page_title */
/** @var string $current_path */
/** @var string $menu_current_path */
/** @var array<int, array<string, mixed>> $header_menu */
/** @var array<int, array<string, mixed>> $left_menu */
/** @var array<int, array<string, mixed>> $right_menu */
/** @var ?string $title */
/** @var ?string $date */
/** @var ?string $author */
?>
```

Templates are trusted code. Relay uses `extract()` and then requires the selected file without sandboxing it. Install themes only from sources you trust.

## Build a Complete Page Shell

The following `themes/my-theme/templates/main.php` example supports all menu positions and optional metadata:

```php
<?php
/** @var array<string, mixed> $metadata */
/** @var string $content_html */
/** @var string $page_title */
/** @var string $menu_current_path */
/** @var array<int, array<string, mixed>> $header_menu */
/** @var array<int, array<string, mixed>> $left_menu */
/** @var array<int, array<string, mixed>> $right_menu */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="generator" content="Relay CMS">
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo url_base('/themes/my-theme/css/theme.css'); ?>">
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>
    <header>
        <a href="<?php echo url_base('/'); ?>">My Relay Site</a>
        <?php if (!empty($header_menu)): ?>
            <?php echo menu_render_header($header_menu, $menu_current_path); ?>
        <?php endif; ?>
    </header>

    <div class="page-layout">
        <?php if (!empty($left_menu)): ?>
            <aside aria-label="Primary sidebar">
                <?php echo menu_render($left_menu, $menu_current_path); ?>
            </aside>
        <?php endif; ?>

        <main id="main-content">
            <?php if ($title !== null): ?>
                <h1><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
            <?php endif; ?>
            <?php if ($date !== null): ?>
                <p>
                    <time datetime="<?php echo htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?>
                    </time>
                    <?php if ($author !== null): ?>
                        by <?php echo htmlspecialchars($author, ENT_QUOTES, 'UTF-8'); ?>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
            <?php echo $content_html; ?>
        </main>

        <?php if (!empty($right_menu)): ?>
            <aside aria-label="Related navigation">
                <?php echo menu_render($right_menu, $menu_current_path); ?>
            </aside>
        <?php endif; ?>
    </div>

    <footer>&copy; <?php echo date('Y'); ?> My Relay Site</footer>
    <script src="<?php echo url_base('/themes/my-theme/js/theme.js'); ?>"></script>
</body>
</html>
```

Only include the script if the file exists in your theme. Empty menu arrays should not create empty navigation landmarks.

## Add an Alternate Template

Create a separate template when a content type needs meaningfully different structure, not merely a different color or spacing rule. For example, `landing.php` might omit sidebars and place a custom `summary` metadata field before the content:

```php
<?php if (isset($metadata['summary'])): ?>
    <p class="intro">
        <?php echo htmlspecialchars((string) $metadata['summary'], ENT_QUOTES, 'UTF-8'); ?>
    </p>
<?php endif; ?>
```

Add `landing` to `theme.json.templates`, then select it with `template: landing`. Continue with [assets and deployment-safe URLs](assets-and-urls).
