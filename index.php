<?php
/**
 * Relay - Main Router and Theme
 *
 * Handles URL routing and renders content with the theme.
 */

// Load Composer autoloader and libraries
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/content.php';
require_once __DIR__ . '/lib/menu.php';
require_once __DIR__ . '/lib/csrf.php';
require_once __DIR__ . '/lib/theme.php';

// Start session
auth_init_session();

// Parse URL path
$request_path = '';

if (isset($_GET['p'])) {
    $request_path = $_GET['p'];
} elseif (isset($_SERVER['REQUEST_URI'])) {
    $request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    // Remove leading slash
    $request_path = ltrim($request_path, '/');
}

// Sanitize path
$current_path = content_sanitize_path($request_path);

if ($current_path === false) {
    // Invalid path, show 404
    http_response_code(404);
    require_once __DIR__ . '/error-404.php';
    exit;
}

// Load content
$content = content_load($current_path);

if ($content === false) {
    // Content not found, show 404
    http_response_code(404);
    require_once __DIR__ . '/error-404.php';
    exit;
}

// Extract metadata and HTML
$metadata = $content['metadata'];
$content_html = $content['html'];

// Get page title
$page_title = content_get_title($metadata, 'Relay');

// Load menus
$header_menu = menu_load('header-menu');
$left_menu = menu_load('left-menu');
$right_menu = menu_load('right-menu');

// Build current path for menu highlighting
$menu_current_path = '/' . $current_path;

// Determine which template to use
$template = $metadata['template'] ?? 'main';

// Prepare template variables
$template_vars = [
    'metadata' => $metadata,
    'content_html' => $content_html,
    'page_title' => $page_title,
    'current_path' => $current_path,
    'menu_current_path' => $menu_current_path,
    'header_menu' => $header_menu,
    'left_menu' => $left_menu,
    'right_menu' => $right_menu,
    // Convenient extractions
    'title' => $metadata['title'] ?? null,
    'date' => $metadata['date'] ?? null,
    'author' => $metadata['author'] ?? null,
];

// Render template
theme_render_template($template, $template_vars);
exit;
