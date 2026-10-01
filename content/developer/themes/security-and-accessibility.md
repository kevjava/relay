---
title: Theme Security and Accessibility
---

Themes execute on the server and produce every public page. Treat output safety and accessible structure as part of the theme contract, not as optional polish.

## Trust Boundaries

Relay's Markdown content is trusted and may contain HTML. `$content_html` is the rendered result and should be output directly:

```html
<?php echo $content_html; ?>
```

Metadata and menu values are plain data. Escape them for the context where they appear:

```html
<h1><?php echo htmlspecialchars((string) $metadata['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
<time datetime="<?php echo htmlspecialchars((string) $metadata['date'], ENT_QUOTES, 'UTF-8'); ?>">
    <?php echo htmlspecialchars((string) $metadata['date'], ENT_QUOTES, 'UTF-8'); ?>
</time>
```

`htmlspecialchars()` is appropriate for HTML text and quoted HTML attributes. JavaScript, JSON, CSS, and URL components have different encoding rules. Prefer data attributes and `json_encode()` with appropriate flags instead of concatenating values into script source.

Validate dynamic choices against an allowlist. For example, map a metadata value such as `color_scheme: ocean` to a known CSS class instead of printing an arbitrary class or filename.

## Trusted PHP

Templates and `lib/menu.php` are executable PHP with access to Relay's process. They are not sandboxed. Theme authors should:

- avoid shell execution and arbitrary file access;
- never derive an include path from metadata, query parameters, or menu data;
- rely on Relay's template selection rather than requiring user-selected files;
- avoid changing sessions, headers, or global settings during normal rendering;
- document every third-party runtime asset and its license.

Site operators should inspect theme PHP before installation and install themes only from trusted sources.

## Accessible Page Structure

Every template should provide:

- a meaningful document title;
- a keyboard-visible skip link targeting the main content;
- one clear `main` landmark;
- semantic `header`, `nav`, `aside`, and `footer` regions where appropriate;
- an ordered heading hierarchy driven by the page title and content;
- descriptive labels where multiple navigation regions exist;
- visible keyboard focus states;
- sufficient text and control contrast;
- useful alternative text for meaningful images and empty `alt` text for decorative images;
- controls that work with a keyboard without requiring pointer gestures.

Do not render empty navigation or sidebar landmarks. Do not duplicate the page title in a way that creates competing level-one headings unless the content model intentionally requires it.

## Responsive and Motion Behavior

Test layouts with no sidebars, each sidebar independently, and both sidebars. Include long words, long menu labels, nested items, zoomed text, and narrow screens. Content and controls must not overlap or require horizontal scrolling at common mobile widths.

Honor `prefers-reduced-motion` for nonessential animation:

```css
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        scroll-behavior: auto;
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
```

Accessible patterns improve a theme, but they do not by themselves prove WCAG or Section 508 conformance. Make compliance claims only after an appropriate audit.

Use the [testing guide](testing-and-debugging) to exercise these requirements.
