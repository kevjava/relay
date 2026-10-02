<?php

require_once __DIR__ . '/json.php';

/**
 * Settings Management Library
 *
 * Handles site-wide configuration including active theme selection.
 */

// Define settings file constant
define('RELAY_SETTINGS_FILE', __DIR__ . '/../config/settings.json');

/**
 * Load settings from JSON file
 *
 * @return array Settings data with defaults
 */
function settings_load(): array
{
    $defaults = [
        'active_theme' => 'default',
        'site_name' => 'Relay CMS',
        'timezone' => 'America/New_York'
    ];

    $settings = relay_json_load(RELAY_SETTINGS_FILE);

    if ($settings === false) {
        return $defaults;
    }

    // Merge with defaults to ensure all keys exist
    return array_merge($defaults, $settings);
}

/**
 * Save settings to JSON file
 *
 * @param array $settings Settings data to save
 * @return bool Success status
 */
function settings_save(array $settings): bool
{
    // Validate settings structure
    if (!settings_validate($settings)) {
        return false;
    }

    return relay_json_save(RELAY_SETTINGS_FILE, $settings);
}

/**
 * Validate settings data structure
 *
 * @param array $settings Settings data to validate
 * @return bool True if valid
 */
function settings_validate(array $settings): bool
{
    // Required fields
    $required = ['active_theme', 'site_name', 'timezone'];

    foreach ($required as $field) {
        if (!isset($settings[$field]) || !is_string($settings[$field])) {
            return false;
        }
    }

    // Validate active_theme format (alphanumeric, dash, underscore)
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $settings['active_theme'])) {
        return false;
    }

    return true;
}

/**
 * Get a specific setting value
 *
 * @param string $key Setting key
 * @param mixed $default Default value if not found
 * @return mixed Setting value or default
 */
function settings_get(string $key, mixed $default = null): mixed
{
    $settings = settings_load();
    return $settings[$key] ?? $default;
}

/**
 * Set a specific setting value
 *
 * @param string $key Setting key
 * @param mixed $value Setting value
 * @return bool Success status
 */
function settings_set(string $key, mixed $value): bool
{
    $settings = settings_load();
    $settings[$key] = $value;
    return settings_save($settings);
}
