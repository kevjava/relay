---
title: Theme Writer Guide
---

A Relay theme is a trusted PHP package that controls public page templates, styles, scripts, images, fonts, and optionally the HTML generated for menus. Relay core continues to own routing, Markdown rendering, content storage, settings, authentication, and menu data.

You should be comfortable with PHP 8.1 or later, HTML, and CSS. Begin with a working Relay installation so you can test each change as you build.

## How Relay Uses a Theme

1. Relay discovers directories under `themes/` that contain `theme.json`.
2. The configured theme is validated and selected from `config/settings.json`.
3. Relay loads the active theme's optional `lib/menu.php`, then its core menu library.
4. The requested page is loaded and its `template` frontmatter selects a PHP template.
5. Relay passes content, metadata, paths, and menus to that template for rendering.

If the configured theme directory does not exist, Relay uses the `default` theme directory. A valid theme must always provide `templates/main.php`.

## Build a Theme

- [Create a theme](./quick-start) with the smallest working example.
- [Define its structure and metadata](./structure-and-metadata).
- [Build templates](./templates) using Relay's complete data contract.
- [Load assets and build URLs](./assets-and-urls) that work in every deployment location.
- [Render navigation](./navigation) or supply custom menu markup.

## Prepare a Theme for Use

- [Apply security and accessibility practices](./security-and-accessibility).
- [Test and debug the theme](./testing-and-debugging).
- [Package and maintain the theme](./packaging).

The framework-free `themes/default/` theme is the clearest starting point. The `themes/uswds/` theme demonstrates integration with a larger design system and locally hosted assets.
