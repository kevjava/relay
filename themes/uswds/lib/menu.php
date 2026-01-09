<?php
/**
 * USWDS Theme Menu Rendering
 *
 * Custom menu rendering functions for U.S. Web Design System.
 * Overrides core menu.php functions with USWDS-specific markup.
 */

/**
 * Render USWDS sidebar navigation menu
 *
 * Generates USWDS-compliant sidebar menu with proper classes and structure.
 * Overrides core menu_render() function.
 *
 * @param array $menu_data Menu items array
 * @param string $current_path Current page path for active state
 * @param int $depth Current nesting depth
 * @return string HTML output
 */
if (!function_exists('menu_render')) {
function menu_render(array $menu_data, string $current_path = '', int $depth = 0): string {
    if (empty($menu_data)) {
        return '';
    }

    $html = '<ul class="usa-sidenav' . ($depth > 0 ? '__sublist' : '') . '">';

    foreach ($menu_data as $item) {
        $label = htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8');
        $url = htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8');
        $is_active = menu_is_active($item['url'], $current_path);
        $has_children = isset($item['children']) && is_array($item['children']) && !empty($item['children']);

        $html .= '<li class="usa-sidenav__item">';
        $html .= '<a href="' . $url . '"';
        if ($is_active) {
            $html .= ' class="usa-current"';
        }
        $html .= '>' . $label . '</a>';

        // Render children recursively
        if ($has_children) {
            $html .= menu_render($item['children'], $current_path, $depth + 1);
        }

        $html .= '</li>';
    }

    $html .= '</ul>';

    return $html;
}
}

/**
 * Render USWDS primary navigation menu
 *
 * Generates USWDS-compliant horizontal navigation for header.
 * Overrides core menu_render_header() function.
 *
 * @param array $menu_data Menu items array
 * @param string $current_path Current page path for active state
 * @return string HTML output
 */
if (!function_exists('menu_render_header')) {
function menu_render_header(array $menu_data, string $current_path = ''): string {
    if (empty($menu_data)) {
        return '';
    }

    $html = '<nav aria-label="Primary navigation" class="usa-nav">';
    $html .= '<ul class="usa-nav__primary usa-accordion">';

    // Counter for unique IDs
    $section_counter = 0;

    foreach ($menu_data as $item) {
        $label = htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8');
        $url = htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8');
        $is_active = menu_is_active($item['url'], $current_path);
        $has_children = isset($item['children']) && is_array($item['children']) && !empty($item['children']);

        $html .= '<li class="usa-nav__primary-item">';

        if ($has_children) {
            // Item with children - use button with accordion
            $section_counter++;
            $section_id = 'nav-section-' . $section_counter;

            $html .= '<button class="usa-accordion__button usa-nav__link';
            if ($is_active) {
                $html .= ' usa-current';
            }
            $html .= '" aria-expanded="false" aria-controls="' . $section_id . '">';
            $html .= '<span>' . $label . '</span>';
            $html .= '</button>';

            // Render submenu
            $html .= '<ul id="' . $section_id . '" class="usa-nav__submenu">';
            foreach ($item['children'] as $child) {
                $child_label = htmlspecialchars($child['label'], ENT_QUOTES, 'UTF-8');
                $child_url = htmlspecialchars($child['url'], ENT_QUOTES, 'UTF-8');
                $child_is_active = menu_is_active($child['url'], $current_path);

                $html .= '<li class="usa-nav__submenu-item">';
                $html .= '<a href="' . $child_url . '"';
                if ($child_is_active) {
                    $html .= ' class="usa-current"';
                }
                $html .= '>' . $child_label . '</a>';
                $html .= '</li>';
            }
            $html .= '</ul>';
        } else {
            // Item without children - simple link
            $html .= '<a class="usa-nav__link';
            if ($is_active) {
                $html .= ' usa-current';
            }
            $html .= '" href="' . $url . '">';
            $html .= '<span>' . $label . '</span>';
            $html .= '</a>';
        }

        $html .= '</li>';
    }

    $html .= '</ul>';
    $html .= '</nav>';

    return $html;
}
}
