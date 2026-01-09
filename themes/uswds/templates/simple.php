<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="generator" content="Relay CMS - USWDS Theme">
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>

    <!-- U.S. Web Design System CSS (local) -->
    <link rel="stylesheet" href="/themes/uswds/css/uswds.min.css">

    <!-- Custom theme CSS -->
    <link rel="stylesheet" href="/themes/uswds/css/theme.css">
</head>
<body>
    <!-- Skip Navigation -->
    <a class="usa-skipnav" href="#main-content">Skip to main content</a>

    <!-- Simple Header -->
    <header class="usa-header usa-header--basic">
        <div class="usa-nav-container">
            <div class="usa-navbar">
                <div class="usa-logo">
                    <em class="usa-logo__text">
                        <a href="/" title="Home">Relay</a>
                    </em>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main id="main-content">
        <div class="usa-section">
            <div class="grid-container">
                <div class="grid-row">
                    <div class="grid-col-12 desktop:grid-col-10 desktop:grid-offset-1">
                        <article>

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
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Simple Footer -->
    <footer class="usa-footer usa-footer--slim">
        <div class="grid-container usa-footer__return-to-top">
            <a href="#">Return to top</a>
        </div>
        <div class="usa-footer__secondary-section">
            <div class="grid-container">
                <div class="grid-row grid-gap">
                    <div class="usa-footer__contact-links desktop:grid-col-12">
                        <p class="text-center">&copy; <?php echo date('Y'); ?> Relay CMS</p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- U.S. Web Design System JS (local) -->
    <script src="/themes/uswds/js/uswds.min.js"></script>
</body>
</html>
