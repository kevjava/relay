# U.S. Web Design System Theme for Relay CMS

A Relay theme built with USWDS 3.7 patterns and local assets for government agencies and organizations. Using USWDS supports accessible implementation, but deployment-specific accessibility and compliance still require testing and review.

For Relay's complete theme contract, read the [Theme Writer Guide](../../content/developer/themes/index.md).

## Features

- **USWDS 3.7 Compliant**: Uses official U.S. Web Design System components and styling
- **Accessibility-oriented**: Uses semantic landmarks, skip navigation, and visible framework focus styles
- **Audit-ready foundation**: Designed to support WCAG and Section 508 review without claiming automatic conformance
- **Responsive Design**: Mobile-first approach with responsive grid layout
- **Skip Navigation**: Keyboard-accessible skip links for main content
- **Semantic HTML**: Proper use of ARIA labels and semantic elements
- **Government Branding**: Professional, official appearance suitable for government use

## Templates

### main.php

Three-column responsive layout with:

- USWDS header with primary navigation
- Left sidebar with side navigation (optional)
- Main content area with USWDS typography
- Right sidebar with secondary navigation (optional)
- USWDS footer with return-to-top link

### simple.php

Single-column minimal layout with:

- Basic USWDS header
- Centered content area (10 columns with offset)
- Slim USWDS footer

## USWDS Components Used

- **usa-header**: Official government site header
- **usa-nav**: Primary navigation component
- **usa-sidenav**: Side navigation for hierarchical menus
- **usa-section**: Content sections with proper spacing
- **usa-prose**: Typography styling for content
- **usa-footer**: Official footer with return-to-top
- **usa-skipnav**: Accessibility skip navigation link
- **grid-container**: Responsive grid system

## Typography

The theme uses USWDS typography classes:

- `font-heading-xl` - Page titles
- `usa-intro` - Introductory text (dates, authors)
- `usa-prose` - Content body text with proper spacing

## Navigation

### Primary Navigation (Header Menu)

Horizontal navigation in the header using `usa-nav__primary`.

### Side Navigation (Left/Right Menus)

Hierarchical side navigation using `usa-sidenav` with support for:

- Nested menu items
- Active state highlighting
- Proper indentation for sub-items

## Customization

### Custom CSS

The theme includes `css/theme.css` with additional styling for:

- Content typography enhancements
- Code blocks and syntax highlighting
- Table styling
- Blockquote styling
- Navigation active states
- Print styles
- Mobile responsive adjustments

### Colors

Default USWDS colors are used:

- Primary: `#005ea2` (USWDS blue)
- Text: `#1b1b1b` (USWDS black)
- Background: `#ffffff` (white)
- Gray: `#f0f0f0` (USWDS gray-5)

To customize colors, modify the CSS custom properties in `theme.css` or override USWDS variables.

## Local Assets

The theme includes USWDS 3.7 assets locally for optimal performance and CSP compliance:

- **CSS**: `/themes/uswds/css/uswds.min.css` (512KB)
- **JS**: `/themes/uswds/js/uswds.min.js` (86KB)
- **Fonts**: `/themes/uswds/fonts/` (180KB total)
  - Source Sans Pro (Regular, Bold, Italic, Bold Italic)
  - Merriweather (Regular, Bold, Italic, Bold Italic)

Local hosting provides:

1. Better performance (no external requests)
2. CSP compliance (no external CDN dependencies)
3. Offline functionality
4. Version stability
5. No font loading delays or FOUT (Flash of Unstyled Text)

## Accessibility Features

- Skip navigation link (keyboard accessible)
- Proper heading hierarchy (h1-h6)
- ARIA labels on navigation regions
- Focus visible indicators
- Semantic HTML5 elements
- Alt text support for images in content
- High contrast colors meeting WCAG AA standards
- Keyboard navigation support

## Browser Support

Matches USWDS 3.7 browser support:

- Chrome (latest 2 versions)
- Firefox (latest 2 versions)
- Safari (latest 2 versions)
- Edge (latest 2 versions)
- iOS Safari (latest 2 versions)
- Chrome for Android (latest version)

## Compliance

The theme uses USWDS components and accessibility-oriented patterns relevant to Section 508, WCAG 2.1 Level AA, the 21st Century IDEA, and OMB Memo M-23-22. Conformance depends on site content, configuration, customization, and testing; using this theme alone does not establish compliance.

## Development

### USWDS Assets

The theme already includes USWDS 3.7 compiled assets locally. To update to a newer version:

```bash
cd themes/uswds

# Download CSS and JS
wget https://cdn.jsdelivr.net/npm/@uswds/uswds@3.7.1/dist/css/uswds.min.css -O css/uswds.min.css
wget https://cdn.jsdelivr.net/npm/@uswds/uswds@3.7.1/dist/js/uswds.min.js -O js/uswds.min.js

# Download fonts
mkdir -p fonts/merriweather fonts/source-sans-pro

# Merriweather fonts
cd fonts/merriweather
wget https://cdn.jsdelivr.net/npm/@uswds/uswds@3.7.1/dist/fonts/merriweather/Latin-Merriweather-Regular.woff2
wget https://cdn.jsdelivr.net/npm/@uswds/uswds@3.7.1/dist/fonts/merriweather/Latin-Merriweather-Bold.woff2
wget https://cdn.jsdelivr.net/npm/@uswds/uswds@3.7.1/dist/fonts/merriweather/Latin-Merriweather-Italic.woff2
wget https://cdn.jsdelivr.net/npm/@uswds/uswds@3.7.1/dist/fonts/merriweather/Latin-Merriweather-BoldItalic.woff2

# Source Sans Pro fonts
cd ../source-sans-pro
wget https://cdn.jsdelivr.net/npm/@uswds/uswds@3.7.1/dist/fonts/source-sans-pro/sourcesanspro-regular-webfont.woff2
wget https://cdn.jsdelivr.net/npm/@uswds/uswds@3.7.1/dist/fonts/source-sans-pro/sourcesanspro-bold-webfont.woff2
wget https://cdn.jsdelivr.net/npm/@uswds/uswds@3.7.1/dist/fonts/source-sans-pro/sourcesanspro-italic-webfont.woff2
wget https://cdn.jsdelivr.net/npm/@uswds/uswds@3.7.1/dist/fonts/source-sans-pro/sourcesanspro-bolditalic-webfont.woff2
```

For advanced customization with npm:

```bash
npm install @uswds/uswds@3.7.1
```

### Custom Sass Compilation

To customize USWDS with Sass:

1. Create `themes/uswds/scss/theme.scss`
2. Import USWDS and override variables:

    ```scss
    // Override USWDS settings
    $theme-color-primary: 'blue-60v';
    $theme-font-type-sans: 'source-sans-pro';

    // Import USWDS
    @import '@uswds/uswds/dist/scss/uswds';

    // Custom styles
    .custom-class {
      // Your styles
    }
    ```

3. Compile to `css/theme.css`

## Resources

- [USWDS Documentation](https://designsystem.digital.gov/)
- [USWDS Components](https://designsystem.digital.gov/components/)
- [USWDS Accessibility](https://designsystem.digital.gov/documentation/accessibility/)
- [Section 508](https://www.section508.gov/)
- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)

## License

This theme follows Relay CMS licensing. USWDS itself is released as open source under a permissive license and is free to use for government and non-government projects.
