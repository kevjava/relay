<?php
/**
 * Relay Theme System
 *
 * Provides template rendering functionality for the Relay CMS.
 * Templates are plain HTML files with PHP blocks that can be specified
 * via the 'template' frontmatter field in markdown files.
 */

// Define theme directory constant
define('RELAY_THEME_DIR', __DIR__ . '/../theme');

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
    $template_path = RELAY_THEME_DIR . '/templates/' . $sanitized . '.html';

    // Resolve real path
    $real_path = realpath($template_path);

    // If file doesn't exist, return false
    if ($real_path === false) {
        return false;
    }

    // Verify the resolved path is within theme/templates directory
    $theme_templates_real = realpath(RELAY_THEME_DIR . '/templates');

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
            die('Template system error: main template not found at ' . RELAY_THEME_DIR . '/templates/main.html');
        }
    }

    // Extract variables into current scope
    extract($variables, EXTR_SKIP);

    // Include template file
    // Template has access to all extracted variables and global scope
    require $template_path;
}
