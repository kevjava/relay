<?php
/**
 * Relay Theme System
 *
 * Provides template rendering functionality for the Relay CMS.
 * Templates are PHP files that can be specified via the 'template'
 * frontmatter field in markdown files.
 */

// Define theme directory constant
define('RELAY_THEMES_DIR', __DIR__ . '/../themes');

/**
 * Get the active theme directory path
 *
 * Returns the active theme directory based on settings.json.
 *
 * @return string Absolute path to active theme directory
 */
function theme_get_active_dir(): string {
    // Load settings to get active theme
    require_once __DIR__ . '/settings.php';
    $active_theme = settings_get('active_theme', 'default');

    // Sanitize theme name
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $active_theme)) {
        $active_theme = 'default';
    }

    $theme_path = RELAY_THEMES_DIR . '/' . $active_theme;

    // Verify theme directory exists
    if (!is_dir($theme_path)) {
        // Fallback to default theme
        $theme_path = RELAY_THEMES_DIR . '/default';
    }

    return $theme_path;
}

/**
 * Sanitize template name to prevent path traversal attacks
 *
 * @param string $template The template name to sanitize
 * @return string|false The sanitized template name, or false if invalid
 */
function theme_sanitize_template_name(string $template): string|false {
    // Remove null bytes
    $template = str_replace("\0", '', $template);

    // Trim whitespace
    $template = trim($template);

    // Empty template means default
    if (empty($template)) {
        return 'main';
    }

    // Only allow alphanumeric, dash, underscore (no slashes!)
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $template)) {
        return false;
    }

    // Reject special names that could be dangerous
    if (in_array($template, ['.', '..'], true)) {
        return false;
    }

    return $template;
}

/**
 * Get the full filesystem path for a template file
 *
 * @param string $template The template name
 * @return string|false The validated filesystem path, or false if invalid/missing
 */
function theme_get_template_path(string $template): string|false {
    $sanitized = theme_sanitize_template_name($template);

    if ($sanitized === false) {
        return false;
    }

    // Build template path
    $template_path = theme_get_active_dir() . '/templates/' . $sanitized . '.php';

    // Resolve real path
    $real_path = realpath($template_path);

    // If file doesn't exist, return false
    if ($real_path === false) {
        return false;
    }

    // Verify the resolved path is within theme/templates directory
    $theme_templates_real = realpath(theme_get_active_dir() . '/templates');

    if ($theme_templates_real === false || !str_starts_with($real_path, $theme_templates_real)) {
        return false;
    }

    return $real_path;
}

/**
 * Check if a template exists
 *
 * @param string $template The template name
 * @return bool True if the template exists, false otherwise
 */
function theme_template_exists(string $template): bool {
    return theme_get_template_path($template) !== false;
}

/**
 * Render a template with provided variables
 *
 * Templates are included with full global scope access.
 * Variables are extracted into the local scope before inclusion.
 *
 * @param string $template The template name to render
 * @param array $variables Variables to make available to the template
 * @return void
 */
function theme_render_template(string $template, array $variables): void {
    // Get the template path
    $template_path = theme_get_template_path($template);

    // If template doesn't exist, try to fall back to main
    if ($template_path === false) {
        if ($template !== 'main') {
            // Log the error
            error_log("Relay CMS: Template '$template' not found, falling back to 'main'");

            // Try main template
            $template_path = theme_get_template_path('main');
        }

        // If main template also doesn't exist, fatal error
        if ($template_path === false) {
            http_response_code(500);
            die('Template system error: main template not found at ' . theme_get_active_dir() . '/templates/main.php');
        }
    }

    // Extract variables into current scope
    extract($variables, EXTR_SKIP);

    // Include template file
    // Template has access to all extracted variables and global scope
    require $template_path;
}

/**
 * Get list of available themes
 *
 * @return array Array of theme names
 */
function theme_list_available(): array {
    if (!is_dir(RELAY_THEMES_DIR)) {
        return [];
    }

    $themes = [];
    $dir = opendir(RELAY_THEMES_DIR);

    if ($dir === false) {
        return [];
    }

    while (($entry = readdir($dir)) !== false) {
        // Skip hidden files and parent directories
        if ($entry[0] === '.') {
            continue;
        }

        $theme_path = RELAY_THEMES_DIR . '/' . $entry;

        // Only include directories with theme.json
        if (is_dir($theme_path) && file_exists($theme_path . '/theme.json')) {
            $themes[] = $entry;
        }
    }

    closedir($dir);
    sort($themes);

    return $themes;
}

/**
 * Load theme metadata from theme.json
 *
 * @param string $theme_name Theme name
 * @return array|false Theme metadata or false if not found/invalid
 */
function theme_get_metadata(string $theme_name): array|false {
    // Sanitize theme name
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $theme_name)) {
        return false;
    }

    $metadata_path = RELAY_THEMES_DIR . '/' . $theme_name . '/theme.json';

    if (!file_exists($metadata_path)) {
        return false;
    }

    $json = file_get_contents($metadata_path);
    $metadata = json_decode($json, true);

    if (!is_array($metadata)) {
        return false;
    }

    // Validate required fields
    $required = ['name', 'version', 'templates'];
    foreach ($required as $field) {
        if (!isset($metadata[$field])) {
            return false;
        }
    }

    return $metadata;
}

/**
 * Validate theme structure
 *
 * Checks if theme has required directories and files.
 *
 * @param string $theme_name Theme name
 * @return bool True if valid theme structure
 */
function theme_validate(string $theme_name): bool {
    // Sanitize theme name
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $theme_name)) {
        return false;
    }

    $theme_path = RELAY_THEMES_DIR . '/' . $theme_name;

    // Check theme directory exists
    if (!is_dir($theme_path)) {
        return false;
    }

    // Check required files/directories
    $required = [
        '/theme.json',
        '/templates',
        '/templates/main.php'
    ];

    foreach ($required as $item) {
        $full_path = $theme_path . $item;

        if (str_ends_with($item, '.json') || str_ends_with($item, '.php')) {
            if (!file_exists($full_path)) {
                return false;
            }
        } else {
            if (!is_dir($full_path)) {
                return false;
            }
        }
    }

    // Validate metadata
    return theme_get_metadata($theme_name) !== false;
}

/**
 * Get the active theme name
 *
 * @return string Active theme name
 */
function theme_get_active(): string {
    require_once __DIR__ . '/settings.php';
    return settings_get('active_theme', 'default');
}

/**
 * Set the active theme
 *
 * @param string $theme_name Theme name to activate
 * @return bool Success status
 */
function theme_set_active(string $theme_name): bool {
    // Validate theme exists and is valid
    if (!theme_validate($theme_name)) {
        return false;
    }

    require_once __DIR__ . '/settings.php';
    return settings_set('active_theme', $theme_name);
}
