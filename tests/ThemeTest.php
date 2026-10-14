<?php

declare(strict_types=1);

require_once __DIR__ . '/Support/FilesystemTestCase.php';

final class ThemeTest extends FilesystemTestCase
{
    private string $themeName = 'phpunit-theme';
    private string $themeDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->themeDir = RELAY_THEMES_DIR . '/' . $this->themeName;

        $this->backupFile(RELAY_SETTINGS_FILE);
        $this->trackPathForCleanup($this->themeDir);

        $this->writeJsonFile(RELAY_SETTINGS_FILE, [
            'active_theme' => $this->themeName,
            'site_name' => 'Relay CMS',
            'timezone' => 'America/New_York',
        ]);

        $this->writeJsonFile($this->themeDir . '/theme.json', [
            'name' => 'PHPUnit Theme',
            'version' => '1.0.0',
            'templates' => ['main'],
        ]);
        $this->writeFile(
            $this->themeDir . '/templates/main.php',
            "<?php echo 'theme:' . \$page_title . '|' . \$content_html; ?>"
        );
        $this->writeFile(
            $this->themeDir . '/lib/testhelper.php',
            "<?php if (!defined('RELAY_THEME_TEST_LIB_LOADED')) { define('RELAY_THEME_TEST_LIB_LOADED', true); }"
        );
    }

    public function testThemeSanitizeAndMetadataHelpersValidateInput(): void
    {
        $this->assertSame('main', theme_sanitize_template_name(''));
        $this->assertSame('simple', theme_sanitize_template_name('simple'));
        $this->assertSame('simple', theme_sanitize_template_name(" \0simple "));
        $this->assertFalse(theme_sanitize_template_name('../bad'));
        $this->assertFalse(theme_sanitize_template_name('bad/name'));

        $metadata = theme_get_metadata('default');

        $this->assertIsArray($metadata);
        $this->assertSame('Relay Default Theme', $metadata['name']);
        $this->assertFalse(theme_get_metadata('../bad-theme'));
        $this->assertFalse(theme_get_metadata('bad theme'));
        $this->assertTrue(theme_validate('default'));
        $this->assertFalse(theme_validate('bad/theme'));
        $this->assertFalse(theme_validate('missing-theme'));
    }

    public function testThemeListAndActiveThemeHelpersUseConfiguredTheme(): void
    {
        $themes = theme_list_available();

        $this->assertContains('default', $themes);
        $this->assertContains('uswds', $themes);
        $this->assertContains($this->themeName, $themes);

        $this->assertSame(
            [
                'configured_name' => $this->themeName,
                'effective_directory' => $this->themeDir,
            ],
            theme_resolve_active()
        );
        $this->assertSame($this->themeName, theme_get_active());
        $this->assertSame($this->themeDir, theme_get_active_dir());
        $this->assertTrue(theme_set_active('default'));
        $this->assertSame('default', theme_get_active());
        $this->assertFalse(theme_set_active('missing-theme'));
    }

    public function testInvalidConfiguredThemeFallsBackToDefaultDirectory(): void
    {
        $this->writeJsonFile(RELAY_SETTINGS_FILE, [
            'active_theme' => '../invalid',
            'site_name' => 'Relay CMS',
            'timezone' => 'America/New_York',
        ]);

        $this->assertSame('../invalid', theme_get_active());
        $this->assertSame(RELAY_THEMES_DIR . '/default', theme_get_active_dir());
        $this->assertSame(realpath(RELAY_THEMES_DIR . '/default/templates/main.php'), theme_get_template_path('main'));
    }

    public function testMissingConfiguredThemePreservesNameAndFallsBackToDefaultDirectory(): void
    {
        $this->writeJsonFile(RELAY_SETTINGS_FILE, [
            'active_theme' => 'missing-theme',
            'site_name' => 'Relay CMS',
            'timezone' => 'America/New_York',
        ]);

        $this->assertSame('missing-theme', theme_get_active());
        $this->assertSame(RELAY_THEMES_DIR . '/default', theme_get_active_dir());
    }

    public function testExistingIncompleteThemeRemainsEffectiveButCannotBeActivated(): void
    {
        $incompleteThemeName = 'phpunit-incomplete-theme';
        $incompleteThemeDir = RELAY_THEMES_DIR . '/' . $incompleteThemeName;
        $this->trackPathForCleanup($incompleteThemeDir);
        $this->ensureDirectory($incompleteThemeDir);
        $this->writeJsonFile(RELAY_SETTINGS_FILE, [
            'active_theme' => $incompleteThemeName,
            'site_name' => 'Relay CMS',
            'timezone' => 'America/New_York',
        ]);

        $this->assertSame($incompleteThemeName, theme_get_active());
        $this->assertSame($incompleteThemeDir, theme_get_active_dir());
        $this->assertFalse(theme_validate($incompleteThemeName));
        $this->assertFalse(theme_set_active($incompleteThemeName));
    }

    public function testTemplateLookupRejectsSymlinkOutsideTemplatesDirectory(): void
    {
        if (!function_exists('symlink')) {
            $this->markTestSkipped('Symbolic links are not supported by this PHP runtime.');
        }

        $outsidePath = sys_get_temp_dir() . '/relay-template-' . bin2hex(random_bytes(8)) . '.php';
        $linkedPath = $this->themeDir . '/templates/escape.php';
        $this->trackPathForCleanup($outsidePath);
        $this->writeFile($outsidePath, '<?php echo "outside";');

        if (!symlink($outsidePath, $linkedPath)) {
            $this->markTestSkipped('The test environment does not permit symbolic links.');
        }

        $this->assertFalse(theme_get_template_path('escape'));
    }

    public function testThemeTemplateLookupRenderAndLibraryLoadingWork(): void
    {
        $templatePath = theme_get_template_path('main');

        $this->assertSame(realpath($this->themeDir . '/templates/main.php'), $templatePath);
        $this->assertTrue(theme_template_exists('main'));
        $this->assertFalse(theme_template_exists('missing'));

        ob_start();
        theme_render_template('missing', [
            'page_title' => 'Hello',
            'content_html' => '<p>World</p>',
        ]);
        $output = ob_get_clean();

        $this->assertSame('theme:Hello|<p>World</p>', $output);
        $this->assertTrue(theme_load_lib('testhelper'));
        $this->assertTrue(defined('RELAY_THEME_TEST_LIB_LOADED'));
        $this->assertFalse(theme_load_lib('../bad-lib'));
        $this->assertFalse(theme_load_lib('bad library'));
        $this->assertFalse(theme_load_lib('missing-lib'));
    }
}
