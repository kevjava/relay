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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="generator" content="Relay CMS">
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?> - Relay</title>
    <link rel="stylesheet" href="/assets/css/relay.css">
</head>
<body>
    <header class="relay-header">
        <div class="relay-container">
            <div class="relay-header-content">
                <div class="relay-logo">
                    <a href="/">Relay</a>
                </div>
                <?php if (!empty($header_menu)): ?>
                    <?php echo menu_render_header($header_menu, $menu_current_path); ?>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="relay-main">
        <div class="relay-container">
            <div class="relay-grid">
                <?php if (!empty($left_menu)): ?>
                <aside class="relay-sidebar relay-sidebar-left">
                    <nav class="relay-sidebar-nav">
                        <?php echo menu_render($left_menu, $menu_current_path); ?>
                    </nav>
                </aside>
                <?php endif; ?>

                <div class="relay-content <?php echo !empty($left_menu) ? 'with-sidebar' : 'full-width'; ?>">
                    <?php if (isset($metadata['title'])): ?>
                        <h1 class="relay-page-title"><?php echo htmlspecialchars($metadata['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
                    <?php endif; ?>

                    <?php if (isset($metadata['date'])): ?>
                        <div class="relay-page-meta">
                            <span class="relay-page-date"><?php echo htmlspecialchars($metadata['date'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php if (isset($metadata['author'])): ?>
                                <span class="relay-page-author">by <?php echo htmlspecialchars($metadata['author'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="relay-content-body">
                        <?php echo $content_html; ?>
                    </div>
                </div>

                <?php if (!empty($right_menu)): ?>
                <aside class="relay-sidebar relay-sidebar-right">
                    <nav class="relay-sidebar-nav">
                        <?php echo menu_render($right_menu, $menu_current_path); ?>
                    </nav>
                </aside>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="relay-footer">
        <div class="relay-container">
            <p>&copy; <?php echo date('Y'); ?> Relay CMS. Lightweight PHP content management.</p>
        </div>
    </footer>
</body>
</html>
