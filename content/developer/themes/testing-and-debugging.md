---
title: Testing and Debugging Themes
---

Validate the theme's PHP first, then exercise its behavior through Relay. Run project commands from the repository root.

## Check PHP Syntax

Check each PHP file in your theme:

```bash
find themes/my-theme -type f -name '*.php' -exec php -l {} \;
```

Every file should report `No syntax errors detected`.

You can also inspect the theme contract from a PHP shell command or temporary test:

```php
var_dump(theme_validate('my-theme'));
var_dump(theme_get_metadata('my-theme'));
var_dump(theme_template_exists('main'));
```

Remember that `theme_template_exists()` checks the active theme, not an arbitrary theme name.

## Run Relay's Tests

Run the PHP unit suite:

```bash
vendor/bin/phpunit
```

Install the browser test dependency and Chromium once when needed:

```bash
npm install
npm run test:browser:install
```

Then run the browser suite:

```bash
npm run test:browser
```

Relay's existing tests cover core theme lookup, validation, fallback, rendering, and library loading. Add focused tests when a theme introduces PHP behavior beyond template markup.

## Manual Test Matrix

| Area | Cases to verify |
| --- | --- |
| Templates | `main`, every alternate template, and a missing alternate name |
| Menus | No menus, header only, each sidebar alone, both sidebars, nested children |
| Active state | Exact route and descendant route |
| Content | No optional metadata, all common metadata, long content, tables, code blocks |
| Escaping | Metadata containing `&`, `<`, `>`, single quotes, and double quotes |
| URLs | Domain root and a subdirectory such as `/relay/` |
| Viewports | Narrow mobile, tablet, desktop, zoomed text |
| Input | Keyboard-only navigation, visible focus, reduced motion |
| Assets | Missing asset behavior, font loading, cache refresh |

Validate the rendered HTML and use an automated accessibility scanner as supporting checks. Neither replaces keyboard testing or review of content structure.

## Diagnose Template Failures

When a requested alternate template is missing, Relay writes this message to the PHP error log and renders `main.php`:

```text
Relay CMS: Template 'template-name' not found, falling back to 'main'
```

If Relay cannot resolve `main.php`, it returns HTTP 500 with a template system error. Confirm that:

1. the active theme directory exists;
2. `templates/main.php` exists and is readable;
3. the theme and template names contain only allowed characters;
4. `theme.json` contains the required fields;
5. the selected template name omits directories and the `.php` extension.

For missing styles or scripts, inspect the browser's network panel and confirm the requested URL includes the deployment base path.

## Pre-Release Checklist

- `theme_validate('my-theme')` returns `true`.
- Every listed template exists and passes `php -l`.
- Missing alternate templates fall back without breaking the page.
- Metadata and custom menu output are escaped.
- Local URLs use `url_base()`.
- All menu and sidebar combinations remain usable.
- Keyboard, mobile, zoom, and reduced-motion checks pass.
- PHP unit and browser tests pass.
- Internal links and local assets return successful responses.
- Third-party licenses and setup steps are documented.

When these checks pass, [package the theme](packaging) for installation.
