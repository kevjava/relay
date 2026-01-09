<?php
/**
 * URL Helper Functions
 *
 * Provides base path detection and URL generation helpers for subdirectory deployment support.
 * Auto-detects the base path from the web server script location without requiring configuration.
 *
 * @package Relay
 * @since 1.1.0
 */

declare(strict_types=1);

/**
 * Get the application base path from the web root
 *
 * Auto-detects the base path by examining where index.php is located.
 * Examples:
 *   - index.php at /var/www/html/index.php → returns ""
 *   - index.php at /var/www/html/relay/index.php → returns "/relay"
 *   - index.php at /var/www/html/sites/my-cms/index.php → returns "/sites/my-cms"
 *
 * Result is cached for performance.
 *
 * @return string Base path (empty string for root deployment, or path with leading slash, no trailing slash)
 */
function url_get_base_path(): string {
    static $base_path = null;

    if ($base_path !== null) {
        return $base_path;
    }

    // Get the directory of the script being executed
    // For /relay/index.php, SCRIPT_NAME is '/relay/index.php'
    // dirname() returns '/relay'
    $script_dir = dirname($_SERVER['SCRIPT_NAME'] ?? '');

    // Normalize: dirname('/index.php') returns '/' which we want as empty string
    if ($script_dir === '/' || $script_dir === '\\') {
        $base_path = '';
    } else {
        // Remove any trailing slashes and ensure leading slash
        $base_path = '/' . trim($script_dir, '/');
    }

    return $base_path;
}

/**
 * Generate a URL with the base path prefix
 *
 * Converts root-relative URLs to base-path-relative URLs for subdirectory deployment.
 * Examples:
 *   - Root deployment: url_base('/assets/css/relay.css') → '/assets/css/relay.css'
 *   - Subdirectory /relay: url_base('/assets/css/relay.css') → '/relay/assets/css/relay.css'
 *
 * @param string $path Root-relative path (should start with /)
 * @return string Full path with base path prefix
 */
function url_base(string $path): string {
    $base_path = url_get_base_path();

    // If path is empty or just '/', return base path or '/'
    if ($path === '' || $path === '/') {
        return $base_path === '' ? '/' : $base_path . '/';
    }

    // Ensure path starts with /
    if ($path[0] !== '/') {
        $path = '/' . $path;
    }

    // For root deployment, return path as-is
    if ($base_path === '') {
        return $path;
    }

    // Combine base path with path
    return $base_path . $path;
}

/**
 * Strip the base path from a full URL path
 *
 * Used in routing to convert REQUEST_URI to a content path.
 * Examples:
 *   - Root deployment: url_strip_base_path('/about') → '/about'
 *   - Subdirectory /relay: url_strip_base_path('/relay/about') → '/about'
 *
 * @param string $full_path Full URL path from REQUEST_URI
 * @return string Path with base path removed
 */
function url_strip_base_path(string $full_path): string {
    $base_path = url_get_base_path();

    // If root deployment, return as-is
    if ($base_path === '') {
        return $full_path;
    }

    // Check if the full path starts with the base path
    if (strpos($full_path, $base_path) === 0) {
        // Remove the base path from the beginning
        $stripped = substr($full_path, strlen($base_path));

        // Ensure we return a path starting with / or empty string
        if ($stripped === '' || $stripped === false) {
            return '/';
        }

        return $stripped;
    }

    // If the path doesn't start with base path, return as-is
    // This handles edge cases where REQUEST_URI might not include the base path
    return $full_path;
}
