<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="generator" content="Relay CMS - USWDS Theme">
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?> - Relay</title>

    <!-- U.S. Web Design System CSS (local) -->
    <link rel="stylesheet" href="/themes/uswds/css/uswds.min.css">

    <!-- Custom theme CSS -->
    <link rel="stylesheet" href="/themes/uswds/css/theme.css">
</head>
<body>
    <!-- USWDS Header -->
    <a class="usa-skipnav" href="#main-content">Skip to main content</a>

    <header class="usa-header usa-header--basic">
        <div class="usa-nav-container">
            <div class="usa-navbar">
                <div class="usa-logo">
                    <em class="usa-logo__text">
                        <a href="/" title="Home">Relay</a>
                    </em>
                </div>
            </div>

            <?php if (!empty($header_menu)): ?>
            <nav aria-label="Primary navigation" class="usa-nav">
                <ul class="usa-nav__primary usa-accordion">
                    <?php foreach ($header_menu as $item):
                        $label = htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8');
                        $url = htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8');
                        $is_active = menu_is_active($item['url'], $menu_current_path);
                    ?>
                    <li class="usa-nav__primary-item">
                        <a class="usa-nav-link <?php echo $is_active ? 'usa-current' : ''; ?>" href="<?php echo $url; ?>">
                            <span><?php echo $label; ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </header>

    <!-- Main Content -->
    <main id="main-content">
        <div class="usa-section">
            <div class="grid-container">
                <?php
                // Determine layout based on which sidebars are present
                $has_left = !empty($left_menu);
                $has_right = !empty($right_menu);
                ?>

                <div class="grid-row grid-gap">
                    <!-- Left Sidebar Navigation -->
                    <?php if ($has_left): ?>
                    <aside class="<?php echo ($has_right ? 'desktop:grid-col-3' : 'desktop:grid-col-4'); ?>">
                        <nav aria-label="Side navigation" class="usa-sidenav">
                            <ul class="usa-sidenav__sublist">
                                <?php
                                // Custom USWDS menu rendering
                                function render_uswds_menu($menu_data, $current_path, $depth = 0) {
                                    foreach ($menu_data as $item) {
                                        $label = htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8');
                                        $url = htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8');
                                        $is_active = menu_is_active($item['url'], $current_path);
                                        $has_children = isset($item['children']) && !empty($item['children']);

                                        echo '<li class="usa-sidenav__item">';
                                        echo '<a href="' . $url . '"';
                                        if ($is_active) {
                                            echo ' class="usa-current"';
                                        }
                                        echo '>' . $label . '</a>';

                                        if ($has_children) {
                                            echo '<ul class="usa-sidenav__sublist">';
                                            render_uswds_menu($item['children'], $current_path, $depth + 1);
                                            echo '</ul>';
                                        }

                                        echo '</li>';
                                    }
                                }
                                render_uswds_menu($left_menu, $menu_current_path);
                                ?>
                            </ul>
                        </nav>
                    </aside>
                    <?php endif; ?>

                    <!-- Main Content -->
                    <article class="<?php
                        if ($has_left && $has_right) {
                            echo 'desktop:grid-col-6';
                        } elseif ($has_left || $has_right) {
                            echo 'desktop:grid-col-8';
                        } else {
                            echo 'desktop:grid-col-12';
                        }
                    ?>">
                        <?php if (isset($metadata['title'])): ?>
                            <h1 class="font-heading-xl margin-y-0"><?php echo htmlspecialchars($metadata['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
                        <?php endif; ?>

                        <?php if (isset($metadata['date'])): ?>
                            <div class="usa-prose margin-top-1 margin-bottom-2">
                                <p class="usa-intro text-base-dark">
                                    <time datetime="<?php echo htmlspecialchars($metadata['date'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($metadata['date'], ENT_QUOTES, 'UTF-8'); ?>
                                    </time>
                                    <?php if (isset($metadata['author'])): ?>
                                        <span class="text-base"> by <?php echo htmlspecialchars($metadata['author'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <div class="usa-prose">
                            <?php echo $content_html; ?>
                        </div>
                    </article>

                    <!-- Right Sidebar Navigation -->
                    <?php if ($has_right): ?>
                    <aside class="desktop:grid-col-3">
                        <nav aria-label="Secondary navigation" class="usa-sidenav">
                            <ul class="usa-sidenav__sublist">
                                <?php render_uswds_menu($right_menu, $menu_current_path); ?>
                            </ul>
                        </nav>
                    </aside>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- USWDS Footer -->
    <footer class="usa-footer">
        <div class="grid-container usa-footer__return-to-top">
            <a href="#">Return to top</a>
        </div>

        <div class="usa-footer__secondary-section">
            <div class="grid-container">
                <div class="grid-row grid-gap">
                    <div class="usa-footer__contact-links desktop:grid-col-12">
                        <div class="usa-footer__primary-content">
                            <p>&copy; <?php echo date('Y'); ?> Relay CMS. Lightweight PHP content management system.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- U.S. Web Design System JS (local) -->
    <script src="/themes/uswds/js/uswds.min.js"></script>
</body>
</html>
