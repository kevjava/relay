---
title: Theme Navigation
---

Relay loads header, left, and right menu data before rendering a template. Themes can use the core renderers or replace their markup with a theme-specific menu library.

## Menu Data

Each menu is an array of items with required string fields `label` and `url`. Sidebar menus may contain nested `children`:

```php
<?php

$menu_data = [
    [
        'label' => 'Developer Guides',
        'url' => '/developer',
        'children' => [
            [
                'label' => 'Theme Writer Guide',
                'url' => '/developer/themes',
            ],
        ],
    ],
];
```

Menu URLs are stored without a deployment base path. Rendering adds that path with `url_base()`.

Templates receive `$header_menu`, `$left_menu`, and `$right_menu`. Each is an array and may be empty.

## Core Menu Functions

```text
menu_render(array $menu_data, string $current_path = '', int $depth = 0): string
menu_render_header(array $menu_data, string $current_path = ''): string
menu_is_active(string $url, string $current_path): bool
```

`menu_render()` produces a nested list and recursively renders `children`. `menu_render_header()` produces a single-level header menu. Both add active-state classes based on `$menu_current_path`.

`menu_is_active()` marks an exact path as active. It also marks a non-root item active when the current path is below it, so `/developer` is active while viewing `/developer/themes/templates`.

Only output landmarks for menus that contain items:

```html
<?php if (!empty($left_menu)): ?>
    <aside>
        <nav aria-label="Developer navigation">
            <?php echo menu_render($left_menu, $menu_current_path); ?>
        </nav>
    </aside>
<?php endif; ?>
```

The core header renderer already emits a `nav` element. Do not wrap it in a second `nav`.

## Override Menu Markup

Create `themes/my-theme/lib/menu.php` when your design system requires different classes or ARIA attributes. The front controller loads this file before `lib/menu.php`. Core rendering functions are defined only if the theme has not already defined them.

This is the current override contract:

- retain signatures compatible with the core functions;
- escape labels and final URLs;
- call `url_base()` for stored menu URLs;
- preserve active-state behavior or document an intentional difference;
- recurse safely when supporting nested children.

`menu.php` is the only theme library automatically loaded by the current front controller. Other files under a theme's `lib/` directory are not automatic extension points.

Example `themes/my-theme/lib/menu.php`:

```php
<?php

function menu_render(array $menu_data, string $current_path = '', int $depth = 0): string {
    if ($menu_data === []) {
        return '';
    }

    $html = '<ul class="site-menu site-menu--level-' . $depth . '">';

    foreach ($menu_data as $item) {
        $label = htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8');
        $url = htmlspecialchars(url_base($item['url']), ENT_QUOTES, 'UTF-8');
        $active = menu_is_active($item['url'], $current_path);
        $class = $active ? ' class="is-active"' : '';

        $html .= '<li' . $class . '><a href="' . $url . '">' . $label . '</a>';

        if (!empty($item['children']) && is_array($item['children'])) {
            $html .= menu_render($item['children'], $current_path, $depth + 1);
        }

        $html .= '</li>';
    }

    return $html . '</ul>';
}

function menu_render_header(array $menu_data, string $current_path = ''): string {
    return '<nav aria-label="Primary">' . menu_render($menu_data, $current_path) . '</nav>';
}
```

Because PHP functions cannot be redefined, do not require the core menu library from the override file. Relay loads it afterward to provide helpers such as `menu_is_active()` and any core renderer your theme did not define.

Review the [security and accessibility requirements](security-and-accessibility) before shipping custom markup.
