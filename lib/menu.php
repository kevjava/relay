<?php
/**
 * Menu Management Library
 *
 * Handles loading, saving, validating, and rendering navigation menus
 * from JSON configuration files.
 */

// Define menu directory
define('RELAY_MENU_DIR', __DIR__ . '/../config');

/**
 * Load a menu from JSON file
 *
 * @param string $menu_name Menu name (e.g., 'header-menu', 'left-menu', 'right-menu')
 * @return array Menu data or empty array if not found
 */
function menu_load(string $menu_name): array {
    // Sanitize menu name
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $menu_name)) {
        return [];
    }

    $file_path = RELAY_MENU_DIR . '/' . $menu_name . '.json';

    if (!file_exists($file_path)) {
        return [];
    }

    $json = file_get_contents($file_path);
    $menu = json_decode($json, true);

    return is_array($menu) ? $menu : [];
}

/**
 * Save a menu to JSON file
 *
 * @param string $menu_name Menu name
 * @param array $menu_data Menu data to save
 * @return bool Success status
 */
function menu_save(string $menu_name, array $menu_data): bool {
    // Sanitize menu name
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $menu_name)) {
        return false;
    }

    // Validate menu structure
    if (!menu_validate($menu_data)) {
        return false;
    }

    $file_path = RELAY_MENU_DIR . '/' . $menu_name . '.json';

    // Create directory if it doesn't exist
    if (!is_dir(RELAY_MENU_DIR)) {
        if (!@mkdir(RELAY_MENU_DIR, 0755, true)) {
            error_log("Relay CMS: Failed to create menu directory: " . RELAY_MENU_DIR);
            return false;
        }
    }

    try {
        set_error_handler(static function($severity, $message, $file, $line) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        $json = json_encode($menu_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $result = file_put_contents($file_path, $json, LOCK_EX);
    } catch (ErrorException $e) {
        error_log("Relay CMS: Failed to write menu file: {$file_path} ({$e->getMessage()})");
        return false;
    } finally {
        restore_error_handler();
    }

    if ($result === false) {
        error_log("Relay CMS: Failed to write menu file: " . $file_path);
        return false;
    }

    return true;
}

/**
 * Validate menu data structure
 *
 * @param array $menu_data Menu data to validate
 * @return bool True if valid
 */
function menu_validate(array $menu_data): bool {
    foreach ($menu_data as $item) {
        if (!is_array($item)) {
            return false;
        }

        // Check required fields
        if (!isset($item['label']) || !isset($item['url'])) {
            return false;
        }

        // Validate types
        if (!is_string($item['label']) || !is_string($item['url'])) {
            return false;
        }

        // Validate children if present
        if (isset($item['children'])) {
            if (!is_array($item['children'])) {
                return false;
            }

            // Recursively validate children
            if (!menu_validate($item['children'])) {
                return false;
            }
        }
    }

    return true;
}

/**
 * Check if a URL is active (matches current path)
 *
 * @param string $url Menu item URL
 * @param string $current_path Current page path
 * @return bool True if active
 */
function menu_is_active(string $url, string $current_path): bool {
    // Normalize paths
    $url = '/' . trim($url, '/');
    $current_path = '/' . trim($current_path, '/');

    // Exact match
    if ($url === $current_path) {
        return true;
    }

    // Check if current path starts with URL (for parent menu items)
    if ($url !== '/' && strpos($current_path, $url . '/') === 0) {
        return true;
    }

    return false;
}

/**
 * Render a menu as HTML
 *
 * Themes can override this function by defining it in themes/{theme}/lib/menu.php.
 * This default implementation provides basic nested list markup.
 *
 * @param array $menu_data Menu data
 * @param string $current_path Current page path
 * @param int $depth Current nesting depth (for CSS classes)
 * @return string HTML output
 */
if (!function_exists('menu_render')) {
function menu_render(array $menu_data, string $current_path = '', int $depth = 0): string {
    if (empty($menu_data)) {
        return '';
    }

    $html = '<ul class="relay-menu relay-menu-depth-' . $depth . '">';

    foreach ($menu_data as $item) {
        $label = htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8');
        $url = htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8');
        $is_active = menu_is_active($item['url'], $current_path);
        $has_children = isset($item['children']) && is_array($item['children']) && !empty($item['children']);

        $classes = ['relay-menu-item'];

        if ($is_active) {
            $classes[] = 'active';
        }

        if ($has_children) {
            $classes[] = 'has-children';
        }

        $class_attr = implode(' ', $classes);

        $html .= '<li class="' . $class_attr . '">';
        $html .= '<a href="' . $url . '">' . $label . '</a>';

        // Render children if present
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
 * Render a simple header menu (horizontal navigation)
 *
 * Themes can override this function by defining it in themes/{theme}/lib/menu.php.
 * This default implementation provides a simple horizontal nav.
 *
 * @param array $menu_data Menu data
 * @param string $current_path Current page path
 * @return string HTML output
 */
if (!function_exists('menu_render_header')) {
function menu_render_header(array $menu_data, string $current_path = ''): string {
    if (empty($menu_data)) {
        return '';
    }

    $html = '<nav class="relay-header-menu"><ul>';

    foreach ($menu_data as $item) {
        $label = htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8');
        $url = htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8');
        $is_active = menu_is_active($item['url'], $current_path);

        $class = $is_active ? ' class="active"' : '';

        $html .= '<li' . $class . '><a href="' . $url . '">' . $label . '</a></li>';
    }

    $html .= '</ul></nav>';

    return $html;
}
}

/**
 * Get all available menus
 *
 * @return array Array of menu names
 */
function menu_list(): array {
    $menus = [];

    if (!is_dir(RELAY_MENU_DIR)) {
        return $menus;
    }

    $files = scandir(RELAY_MENU_DIR);

    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'json' && strpos($file, 'menu') !== false) {
            $menus[] = pathinfo($file, PATHINFO_FILENAME);
        }
    }

    sort($menus);

    return $menus;
}

/**
 * Convert flat menu array to nested structure based on indentation
 *
 * @param array $flat_items Flat array of menu items with 'indent' property
 * @return array Nested menu structure
 */
function menu_flatten_to_nested(array $flat_items): array {
    $nested = [];
    $stack = [];

    foreach ($flat_items as $item) {
        $indent = $item['indent'] ?? 0;

        // Remove indent property from final structure
        unset($item['indent']);

        // Find parent at correct level
        while (count($stack) > 0 && end($stack)['indent'] >= $indent) {
            array_pop($stack);
        }

        if (empty($stack)) {
            // Top level item
            $nested[] = $item;
            $stack[] = ['indent' => $indent, 'item' => &$nested[count($nested) - 1]];
        } else {
            // Child item
            $parent = &$stack[count($stack) - 1]['item'];

            if (!isset($parent['children'])) {
                $parent['children'] = [];
            }

            $parent['children'][] = $item;
            $stack[] = ['indent' => $indent, 'item' => &$parent['children'][count($parent['children']) - 1]];
        }
    }

    return $nested;
}

/**
 * Convert nested menu structure to flat array with indentation
 *
 * @param array $nested_items Nested menu structure
 * @param int $indent Current indentation level
 * @return array Flat array with 'indent' property
 */
function menu_nested_to_flat(array $nested_items, int $indent = 0): array {
    $flat = [];

    foreach ($nested_items as $item) {
        $children = $item['children'] ?? [];
        unset($item['children']);

        $item['indent'] = $indent;
        $flat[] = $item;

        // Add children recursively
        if (!empty($children)) {
            $flat = array_merge($flat, menu_nested_to_flat($children, $indent + 1));
        }
    }

    return $flat;
}
