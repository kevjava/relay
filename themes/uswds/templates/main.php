<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="generator" content="Relay CMS - USWDS Theme">
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>

    <!-- U.S. Web Design System CSS (local) -->
    <link rel="stylesheet" href="<?php echo url_base('/themes/uswds/css/uswds.min.css'); ?>">

    <!-- Custom theme CSS -->
    <link rel="stylesheet" href="<?php echo url_base('/themes/uswds/css/theme.css'); ?>">
</head>
<body>
    <!-- USWDS Header -->
    <a class="usa-skipnav" href="#main-content">Skip to main content</a>

    <div class="usa-overlay"></div>

    <header class="usa-header usa-header--basic">
        <div class="usa-nav-container">
            <div class="usa-navbar">
                <div class="usa-logo">
                    <em class="usa-logo__text">
                        <a href="<?php echo url_base('/'); ?>" title="Home">Relay</a>
                    </em>
                </div>
                <button type="button" class="usa-menu-btn">Menu</button>
            </div>

            <?php if (!empty($header_menu)): ?>
            <?php echo menu_render_header($header_menu, $menu_current_path); ?>
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
                    <aside class="<?php echo($has_right ? 'desktop:grid-col-3' : 'desktop:grid-col-4'); ?>">
                        <?php echo menu_render($left_menu, $menu_current_path); ?>
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

                        <div class="usa-prose">
                            <?php echo $content_html; ?>
                        </div>

                        <hr class="margin-top-2"/>
                        <div class="grid-container padding-x-0">
                            <div class="grid-row">
                                <?php if (isset($metadata['title'])): ?>
                                    <div class="grid-col">
                                        <p class="text-base-dark margin-y-0">
                                            <span class="text-base"><?php echo htmlspecialchars($metadata['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        </p>
                                    </div>
                                <?php endif; ?>

                                <div class="grid-col">
                                    <p class="text-base-dark text-right margin-y-0">
                                        <?php if (isset($metadata['date'])): ?>
                                            <time datetime="<?php echo htmlspecialchars($metadata['date'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars($metadata['date'], ENT_QUOTES, 'UTF-8'); ?>
                                            </time>
                                        <?php endif; ?>
                                        <?php if (isset($metadata['author'])): ?>
                                            <span class="text-italic"> by <?php echo htmlspecialchars($metadata['author'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </article>


                    <!-- Right Sidebar Navigation -->
                    <?php if ($has_right): ?>
                    <aside class="desktop:grid-col-3">
                        <?php echo menu_render($right_menu, $menu_current_path); ?>
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
    <script src="<?php echo url_base('/themes/uswds/js/uswds.min.js'); ?>"></script>
</body>
</html>
