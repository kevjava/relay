---
title: Theme Quick Start
---

This tutorial builds a minimal theme named `my-theme`. Run commands from the Relay repository root.

## 1. Create the Theme Directories

```bash
mkdir -p themes/my-theme/templates themes/my-theme/css
```

Only `templates/` is required. The `css/` directory is a convention that keeps this example self-contained.

## 2. Add Theme Metadata

Create `themes/my-theme/theme.json`:

```json
{
  "name": "My Relay Theme",
  "description": "A small theme built with the Relay theme guide.",
  "version": "1.0.0",
  "author": "Your Name",
  "templates": [
    "main"
  ],
  "default_template": "main",
  "features": [
    "Responsive layout",
    "Header navigation"
  ]
}
```

Relay requires `name`, `version`, and `templates`. The other fields document the theme for people and future tooling.

## 3. Add the Main Template

Create `themes/my-theme/templates/main.php`:

```php
<?php
/** @var array<string, mixed> $metadata */
/** @var string $content_html */
/** @var string $page_title */
/** @var string $menu_current_path */
/** @var array<int, array<string, mixed>> $header_menu */
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
    <header class="site-header">
        <a class="site-name" href="<?php echo url_base('/'); ?>">My Relay Site</a>
        <?php if (!empty($header_menu)): ?>
            <?php echo menu_render_header($header_menu, $menu_current_path); ?>
        <?php endif; ?>
    </header>
    <main id="main-content">
        <?php if (isset($metadata['title'])): ?>
            <h1><?php echo htmlspecialchars((string) $metadata['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
        <?php endif; ?>
        <?php echo $content_html; ?>
    </main>
    <footer>
        <p>&copy; <?php echo date('Y'); ?> My Relay Site</p>
    </footer>
</body>
</html>
```

`$content_html` is rendered Markdown and is intentionally emitted as HTML. Escape metadata and other plain-text values before output.

## 4. Add the Stylesheet

Create `themes/my-theme/css/theme.css`:

```css
:root {
    color-scheme: light;
    font-family: Georgia, serif;
    line-height: 1.6;
}

body {
    margin: 0;
    color: #1f2933;
    background: #ffffff;
}

.skip-link {
    position: absolute;
    left: 1rem;
    top: -4rem;
}

.skip-link:focus {
    top: 1rem;
}

.site-header,
main,
footer {
    width: min(72rem, calc(100% - 2rem));
    margin-inline: auto;
    padding-block: 1rem;
}

a:focus-visible {
    outline: 3px solid #ffbf47;
    outline-offset: 3px;
}
```

## 5. Activate the Theme

Set `active_theme` in `config/settings.json`:

```json
{
  "active_theme": "my-theme"
}
```

Preserve any other settings already in that file. Relay falls back to the `default` theme if the configured theme directory cannot be found.

## 6. Try It with Content

Create `content/theme-example.md`:

```markdown
---
title: Theme Example
template: main
---

This page is rendered by **My Relay Theme**.
```

Visit `/theme-example`. If Relay is installed in a subdirectory such as `/relay`, visit `/relay/theme-example` instead. Also visit a nested content route to confirm that stylesheet URLs still resolve.

## Next Steps

Learn which [files and metadata](structure-and-metadata) Relay requires, then expand the theme with the full [template data contract](templates).
