---
title: Packaging Relay Themes
---

A distributable Relay theme is one self-contained directory that can be copied into `themes/` and inspected before activation.

## Include

- `theme.json` with an accurate version and template list;
- `templates/main.php` and every advertised alternate template;
- all CSS, JavaScript, images, and fonts required at runtime;
- an optional `lib/menu.php` when the theme customizes menu markup;
- a theme README with installation and customization instructions;
- license and attribution files required by third-party assets.

Document the supported Relay and PHP versions, available templates, expected metadata fields, asset build process, browser support, and notable upgrade steps.

## Exclude

- site-specific files from `content/`;
- `config/settings.json` and menu configuration;
- users, credentials, tokens, or environment files;
- generated test results and coverage reports;
- editor, operating-system, and machine-specific files;
- dependencies that are used only to build assets, unless the source package intentionally supports rebuilding.

A theme should not replace or patch Relay core files. Propose core changes separately when the supported theme API is insufficient.

## Prepare a Release

1. Update the version and template list in `theme.json`.
2. Build production assets and remove stale generated files.
3. Run the syntax, test, accessibility, and manual checks from the testing guide.
4. Review the archive on a clean Relay installation.
5. Record changes and any operator action required during upgrade.
6. Package the theme directory itself, preserving its stable directory name.

The extracted result should look like `themes/my-theme/theme.json`, not `themes/my-theme/my-theme/theme.json`.

## Install and Roll Back

1. Read and inspect the theme's PHP and third-party licenses.
2. Copy the theme directory into `themes/`.
3. Confirm its required structure and metadata.
4. Record the current `active_theme` value.
5. Set `active_theme` in `config/settings.json` to the new directory name.
6. Verify the home page, nested routes, every template, menus, and assets.

To roll back, restore the previous `active_theme` value. Do not delete the previous theme until the replacement has been verified.

## Maintain Compatibility

Treat template variables, helper signatures, and output conventions as integration points. Before supporting a new Relay release:

- compare the template variable array in `index.php`;
- review theme and menu functions in `lib/theme.php` and `lib/menu.php`;
- run the current Relay test suites;
- test root and subdirectory deployments;
- document any required template or asset migration.

Return to the [Theme Writer Guide](../themes) or review the [testing checklist](testing-and-debugging) before release.
