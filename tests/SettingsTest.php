<?php

declare(strict_types=1);

require_once __DIR__ . '/Support/FilesystemTestCase.php';

final class SettingsTest extends FilesystemTestCase
{
    public function testSettingsLoadReturnsDefaultsWhenFileIsMissing(): void
    {
        $this->backupFile(RELAY_SETTINGS_FILE);
        $this->deleteSettingsFileIfPresent();

        $settings = settings_load();

        $this->assertSame('default', $settings['active_theme']);
        $this->assertSame('Relay CMS', $settings['site_name']);
        $this->assertSame('America/New_York', $settings['timezone']);
    }

    public function testSettingsValidateEnforcesRequiredStringFields(): void
    {
        $this->assertTrue(settings_validate([
            'active_theme' => 'default',
            'site_name' => 'Relay CMS',
            'timezone' => 'America/New_York',
        ]));

        $this->assertFalse(settings_validate([
            'active_theme' => 'bad/theme',
            'site_name' => 'Relay CMS',
            'timezone' => 'America/New_York',
        ]));

        $this->assertFalse(settings_validate([
            'active_theme' => 'default',
            'site_name' => 'Relay CMS',
        ]));
    }

    public function testSettingsSaveGetAndSetPersistChanges(): void
    {
        $this->backupFile(RELAY_SETTINGS_FILE);

        $settings = [
            'active_theme' => 'default',
            'site_name' => 'Unit Test Site',
            'timezone' => 'UTC',
        ];

        $this->assertTrue(settings_save($settings));
        $this->assertSame('Unit Test Site', settings_get('site_name'));
        $this->assertTrue(settings_set('active_theme', 'uswds'));
        $this->assertSame('uswds', settings_get('active_theme'));
        $this->assertFalse(settings_save([
            'active_theme' => 'bad/theme',
            'site_name' => 'Bad',
            'timezone' => 'UTC',
        ]));
    }

    private function deleteSettingsFileIfPresent(): void
    {
        if (file_exists(RELAY_SETTINGS_FILE)) {
            unlink(RELAY_SETTINGS_FILE);
        }
    }
}
