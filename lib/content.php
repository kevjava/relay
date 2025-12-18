<?php
/**
 * Content Management Library
 *
 * Handles loading, parsing, and rendering of Markdown content files
 * with frontmatter support and path traversal protection.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Define content directory
define('RELAY_CONTENT_DIR', __DIR__ . '/../content');

/**
 * Sanitize and validate a content path
 *
 * @param string $path Requested path
 * @return string|false Sanitized path or false if invalid
 */
function content_sanitize_path(string $path): string|false {
    // Remove null bytes
    $path = str_replace("\0", '', $path);

    // Remove leading/trailing slashes
    $path = trim($path, '/');

    // Empty path means index
    if (empty($path)) {
        $path = 'index';
    }

    // Split path into parts
    $parts = explode('/', $path);
    $clean_parts = [];

    foreach ($parts as $part) {
        // Remove empty parts
        if (empty($part)) {
            continue;
        }

        // Reject path traversal attempts
        if ($part === '.' || $part === '..') {
            return false;
        }

        // Only allow alphanumeric, dash, underscore
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $part)) {
            return false;
        }

        $clean_parts[] = $part;
    }

    // Reconstruct path
    if (empty($clean_parts)) {
        return 'index';
    }

    return implode('/', $clean_parts);
}

/**
 * Get the full filesystem path for a content file
 *
 * @param string $path Sanitized content path
 * @return string|false Full filesystem path or false if invalid
 */
function content_get_file_path(string $path): string|false {
    $sanitized = content_sanitize_path($path);

    if ($sanitized === false) {
        return false;
    }

    // Build file path
    $file_path = RELAY_CONTENT_DIR . '/' . $sanitized . '.md';

    // Resolve real path
    $real_path = realpath($file_path);

    // If file doesn't exist, realpath returns false
    if ($real_path === false) {
        return false;
    }

    // Verify the resolved path is within content directory
    $content_dir_real = realpath(RELAY_CONTENT_DIR);

    if (strpos($real_path, $content_dir_real) !== 0) {
        return false;
    }

    return $real_path;
}

/**
 * Check if a content file exists
 *
 * @param string $path Content path
 * @return bool True if file exists
 */
function content_exists(string $path): bool {
    return content_get_file_path($path) !== false;
}

/**
 * Parse YAML-style frontmatter from Markdown content
 *
 * @param string $markdown Markdown content
 * @return array Array with 'metadata' and 'content' keys
 */
function content_parse_frontmatter(string $markdown): array {
    $metadata = [];
    $content = $markdown;

    // Check if content starts with frontmatter delimiter
    if (strpos($markdown, "---\n") === 0 || strpos($markdown, "---\r\n") === 0) {
        // Find the closing delimiter
        $end_pos = strpos($markdown, "\n---\n", 4);

        if ($end_pos === false) {
            $end_pos = strpos($markdown, "\n---\r\n", 4);
        }

        if ($end_pos !== false) {
            // Extract frontmatter
            $frontmatter = substr($markdown, 4, $end_pos - 4);
            $content = substr($markdown, $end_pos + 5); // Skip closing delimiter

            // Parse frontmatter (simple key: value format)
            $lines = explode("\n", $frontmatter);

            foreach ($lines as $line) {
                $line = trim($line);

                if (empty($line)) {
                    continue;
                }

                // Split on first colon
                $colon_pos = strpos($line, ':');

                if ($colon_pos !== false) {
                    $key = trim(substr($line, 0, $colon_pos));
                    $value = trim(substr($line, $colon_pos + 1));

                    // Remove quotes if present
                    $value = trim($value, '"\'');

                    $metadata[$key] = $value;
                }
            }
        }
    }

    return [
        'metadata' => $metadata,
        'content' => trim($content),
    ];
}

/**
 * Render Markdown to HTML using Parsedown Extra
 *
 * @param string $markdown Markdown content
 * @return string HTML output
 */
function content_render_markdown(string $markdown): string {
    $parsedown = new \ParsedownExtra();

    // Enable security features

    // ####################################################################
    // # SECURITY WARNING: POTENTIAL XSS VULNERABILITY
    // ####################################################################
    // # setSafeMode is disabled to allow raw HTML in Markdown content.
    // # This is currently considered a low risk because content files can
    // # only be edited by trusted users with direct filesystem access.
    // #
    // # If a web-based content editor is EVER implemented, this MUST be
    // # changed to `setSafeMode(true)` to prevent Cross-Site Scripting (XSS)
    // # attacks from untrusted user input.
    // ####################################################################
    $parsedown->setSafeMode(false); // Allow HTML in markdown
    $parsedown->setMarkupEscaped(false);

    return $parsedown->text($markdown);
}

/**
 * Load and parse a content file
 *
 * @param string $path Content path
 * @return array|false Array with 'metadata' and 'html' keys, or false if not found
 */
function content_load(string $path): array|false {
    $file_path = content_get_file_path($path);

    if ($file_path === false) {
        return false;
    }

    // Read file content
    $markdown = file_get_contents($file_path);

    if ($markdown === false) {
        return false;
    }

    // Parse frontmatter
    $parsed = content_parse_frontmatter($markdown);

    // Render markdown to HTML
    $html = content_render_markdown($parsed['content']);

    return [
        'metadata' => $parsed['metadata'],
        'html' => $html,
    ];
}

/**
 * Get page title from metadata or default
 *
 * @param array $metadata Page metadata
 * @param string $default Default title
 * @return string Page title
 */
function content_get_title(array $metadata, string $default = 'Relay'): string {
    return $metadata['title'] ?? $default;
}

/**
 * List all content files in a directory
 *
 * @param string $path Directory path (relative to content dir)
 * @return array Array of content file paths
 */
function content_list_files(string $path = ''): array {
    $sanitized = content_sanitize_path($path);

    if ($sanitized === false) {
        return [];
    }

    $dir_path = RELAY_CONTENT_DIR;

    if (!empty($sanitized) && $sanitized !== 'index') {
        $dir_path .= '/' . $sanitized;
    }

    // Check if directory exists
    if (!is_dir($dir_path)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir_path, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'md') {
            // Get relative path
            $relative_path = substr($file->getPathname(), strlen(RELAY_CONTENT_DIR) + 1);
            // Remove .md extension
            $relative_path = substr($relative_path, 0, -3);

            $files[] = $relative_path;
        }
    }

    sort($files);

    return $files;
}
