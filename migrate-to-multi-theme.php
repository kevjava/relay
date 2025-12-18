<?php
/**
 * Migration Script - Single Theme to Multi-Theme
 *
 * Migrates existing theme/ directory to themes/default/
 * and creates initial settings.json file.
 */

echo "Relay CMS - Multi-Theme Migration Tool\n";
echo "======================================\n\n";

$base_dir = __DIR__;
$old_theme_dir = $base_dir . '/theme';
$new_themes_dir = $base_dir . '/themes';
$new_default_dir = $new_themes_dir . '/default';
$settings_file = $base_dir . '/config/settings.json';

// Check if already migrated
if (is_dir($new_themes_dir)) {
    echo "WARNING: themes/ directory already exists.\n";
    echo "Migration may have already been completed.\n";
    echo "Continue anyway? (y/n): ";
    $confirm = trim(fgets(STDIN));
    if (strtolower($confirm) !== 'y') {
        echo "Migration cancelled.\n";
        exit(0);
    }
}

// Check if theme/ directory exists
if (!is_dir($old_theme_dir)) {
    echo "ERROR: theme/ directory not found.\n";
    echo "Nothing to migrate.\n";
    exit(1);
}

echo "This will:\n";
echo "1. Create themes/ directory\n";
echo "2. Copy theme/ contents to themes/default/\n";
echo "3. Create themes/default/theme.json\n";
echo "4. Create config/settings.json with active_theme='default'\n";
echo "5. Keep original theme/ directory intact (backup)\n\n";

echo "Proceed with migration? (y/n): ";
$confirm = trim(fgets(STDIN));

if (strtolower($confirm) !== 'y') {
    echo "Migration cancelled.\n";
    exit(0);
}

echo "\nStarting migration...\n";

// Step 1: Create themes directory
if (!is_dir($new_themes_dir)) {
    mkdir($new_themes_dir, 0755);
    echo "✓ Created themes/ directory\n";
}

// Step 2: Copy theme to themes/default
if (!is_dir($new_default_dir)) {
    // Recursive copy function
    function recursive_copy($src, $dst) {
        $dir = opendir($src);
        @mkdir($dst, 0755);

        while (($file = readdir($dir)) !== false) {
            if ($file !== '.' && $file !== '..') {
                if (is_dir($src . '/' . $file)) {
                    recursive_copy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }

        closedir($dir);
    }

    recursive_copy($old_theme_dir, $new_default_dir);
    echo "✓ Copied theme/ to themes/default/\n";
} else {
    echo "⚠ themes/default/ already exists, skipping copy\n";
}

// Step 3: Create theme.json
$theme_json_path = $new_default_dir . '/theme.json';
if (!file_exists($theme_json_path)) {
    $theme_metadata = [
        'name' => 'Relay Default Theme',
        'description' => 'Migrated from original theme directory',
        'version' => '1.0.0',
        'author' => 'Relay CMS',
        'templates' => ['main', 'simple'],
        'default_template' => 'main'
    ];

    file_put_contents(
        $theme_json_path,
        json_encode($theme_metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
    chmod($theme_json_path, 0644);
    echo "✓ Created themes/default/theme.json\n";
} else {
    echo "⚠ themes/default/theme.json already exists, skipping\n";
}

// Step 4: Create settings.json
if (!file_exists($settings_file)) {
    $settings = [
        'active_theme' => 'default',
        'site_name' => 'Relay CMS',
        'timezone' => 'America/New_York'
    ];

    file_put_contents(
        $settings_file,
        json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
    chmod($settings_file, 0666);
    echo "✓ Created config/settings.json\n";
} else {
    echo "⚠ config/settings.json already exists, skipping\n";
}

echo "\nMigration complete!\n\n";
echo "Next steps:\n";
echo "1. Test your site at the homepage\n";
echo "2. Log into admin interface and verify theme settings\n";
echo "3. Once verified, you can optionally remove the old theme/ directory\n";
echo "4. Create new themes in themes/ directory\n\n";
echo "Note: Original theme/ directory is preserved as backup.\n";
