---
title: Theme Structure and Metadata
---

Relay discovers themes by directory and validates a small required contract. Everything else in a theme is an authoring convention.

## Directory Structure

```text
themes/
└── my-theme/
    ├── theme.json                 # Required
    ├── templates/                 # Required
    │   ├── main.php               # Required
    │   └── landing.php            # Optional
    ├── css/                        # Optional convention
    │   └── theme.css
    ├── js/                         # Optional convention
    │   └── theme.js
    ├── assets/                     # Optional convention
    ├── fonts/                      # Optional convention
    ├── img/                        # Optional convention
    ├── lib/                        # Optional extension code
    │   └── menu.php
    └── README.md                   # Recommended
```

The theme directory name may contain only ASCII letters, numbers, hyphens, and underscores. Use a stable lowercase name such as `my-theme`, because templates currently include that name in public asset URLs.

## Required Metadata

`theme.json` must contain these fields for `theme_validate()` to accept the theme:

| Field | Purpose |
| --- | --- |
| `name` | Human-readable theme name |
| `version` | Theme release version |
| `templates` | Names of templates the theme intends to provide |

Example:

```json
{
  "name": "My Relay Theme",
  "version": "1.0.0",
  "templates": [
    "main",
    "landing"
  ]
}
```

Relay currently checks that these fields exist, but does not validate their types or verify that every entry in `templates` has a matching PHP file. Keep the list synchronized yourself.

## Descriptive Metadata

The bundled themes also use these optional fields:

| Field | Purpose |
| --- | --- |
| `description` | Short summary of the visual design and intended use |
| `author` | Person or organization maintaining the theme |
| `default_template` | Documents the intended default template |
| `features` | Human-readable list of notable capabilities |

`default_template` is descriptive today. Relay always defaults and falls back to a template named `main`, regardless of this field.

Use semantic versions such as `1.2.0`, and update the version when a release changes markup, assets, or template behavior.

## Discovery, Validation, and Activation

Relay provides these theme functions:

```text
theme_list_available(): array
theme_get_metadata(string $theme_name): array|false
theme_validate(string $theme_name): bool
theme_get_active(): string
theme_set_active(string $theme_name): bool
```

`theme_list_available()` lists theme directories that contain `theme.json`. Listing does not guarantee full validity. `theme_validate()` additionally requires `templates/` and `templates/main.php`, and validates the metadata's required fields.

The active theme name is stored in `config/settings.json`:

```json
{
  "active_theme": "my-theme"
}
```

If the configured name is invalid or its directory is missing, template lookup uses `themes/default/`. Theme code should not edit settings as part of page rendering.

Next, learn how Relay [selects and renders templates](templates).
