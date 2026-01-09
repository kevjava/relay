<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="generator" content="Relay CMS">
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?> - Relay</title>
    <link rel="stylesheet" href="<?php echo url_base('/assets/css/relay.css'); ?>">
    <link rel="stylesheet" href="<?php echo url_base('/themes/default/css/default.css'); ?>">
</head>
<body>
    <!-- Full-width Header -->
    <header class="relay-header">
        <div class="relay-container">
            <div class="relay-header-content">
                <div class="relay-logo">
                    <a href="<?php echo url_base('/'); ?>">Relay</a>
                </div>
                <?php if (!empty($header_menu)): ?>
                    <?php echo menu_render_header($header_menu, $menu_current_path); ?>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Three-Column Layout: Left Menu | Content | Right Menu -->
    <main class="relay-main">
        <div class="relay-container">
            <?php
            // Determine grid class based on which sidebars are present
            $has_left = !empty($left_menu);
            $has_right = !empty($right_menu);
            $grid_class = 'relay-grid';

            if ($has_left && $has_right) {
                $grid_class .= ' three-column';
            } elseif ($has_left) {
                $grid_class .= ' two-column-left';
            } elseif ($has_right) {
                $grid_class .= ' two-column-right';
            } else {
                $grid_class .= ' single-column';
            }
            ?>
            <div class="<?php echo $grid_class; ?>">
                <!-- Left Column: Navigation -->
                <?php if ($has_left): ?>
                <aside class="relay-sidebar relay-sidebar-left">
                    <nav class="relay-sidebar-nav">
                        <?php echo menu_render($left_menu, $menu_current_path); ?>
                    </nav>
                </aside>
                <?php endif; ?>

                <!-- Center Column: Main Content -->
                <article class="relay-content">
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
                </article>

                <!-- Right Column: Secondary Navigation -->
                <?php if ($has_right): ?>
                <aside class="relay-sidebar relay-sidebar-right">
                    <nav class="relay-sidebar-nav">
                        <?php echo menu_render($right_menu, $menu_current_path); ?>
                    </nav>
                </aside>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Full-width Footer -->
    <footer class="relay-footer">
        <div class="relay-container">
            <p>&copy; <?php echo date('Y'); ?> Relay CMS. Lightweight PHP content management.</p>
        </div>
    </footer>
</body>
</html>
