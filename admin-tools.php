#!/usr/bin/env php
<?php
/**
 * Relay - CLI Administration Tools
 *
 * Command-line utilities for user management and system initialization.
 */

// Check if running from CLI
if (php_sapi_name() !== 'cli') {
    die('This script must be run from the command line.');
}

// Load libraries
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/menu.php';

// Display banner
echo "\n";
echo "╔════════════════════════════════════════╗\n";
echo "║    RELAY ADMINISTRATION TOOLS          ║\n";
echo "║    Lightweight PHP CMS                 ║\n";
echo "╚════════════════════════════════════════╝\n";
echo "\n";

// Parse command and arguments
$command = $argv[1] ?? '';
$args = array_slice($argv, 2);

// Command routing
switch ($command) {
    case 'init':
        command_init();
        break;

    case 'create-user':
        command_create_user($args);
        break;

    case 'reset-password':
        command_reset_password($args);
        break;

    case 'list-users':
        command_list_users();
        break;

    case 'help':
    case '--help':
    case '-h':
        command_help();
        break;

    default:
        echo "Unknown command: $command\n\n";
        command_help();
        exit(1);
}

/**
 * Initialize a fresh Relay installation
 */
function command_init() {
    echo "Initializing Relay installation...\n\n";

    // Create directories
    $directories = [
        'lib',
        'content',
        'config',
        'assets/css',
        'assets/js',
        'assets/img',
    ];

    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            echo "✓ Created directory: $dir\n";
        } else {
            echo "  Directory already exists: $dir\n";
        }
    }

    // Create .htaccess for content directory
    $content_htaccess = "Require all denied\n";
    if (!file_exists('content/.htaccess')) {
        file_put_contents('content/.htaccess', $content_htaccess);
        echo "✓ Created content/.htaccess\n";
    } else {
        echo "  File already exists: content/.htaccess\n";
    }

    // Create .htaccess for config directory
    $config_htaccess = "Require all denied\n";
    if (!file_exists('config/.htaccess')) {
        file_put_contents('config/.htaccess', $config_htaccess);
        echo "✓ Created config/.htaccess\n";
    } else {
        echo "  File already exists: config/.htaccess\n";
    }

    // Create sample content
    $sample_content = <<<'MD'
---
title: Welcome to Relay
date: 2024-12-17
author: Relay CMS
---

# Welcome to Relay

Relay is a lightweight PHP content management system designed for simplicity and security.

## Getting Started

- Content is managed through Markdown files in the `/content` directory
- Menus can be edited through the admin interface at `/admin.php`
- Users are managed via CLI tools: `php admin-tools.php`

## Features

- **Markdown Support**: Write content in simple Markdown format
- **Frontmatter**: Add metadata to pages using YAML-style frontmatter
- **Multiple Menus**: Header, left sidebar, and right sidebar navigation
- **Secure**: CSRF protection, rate limiting, and secure password hashing
- **No Database**: All data stored in JSON and Markdown files

## Administration

Log in to the [admin panel](/admin.php) to manage menus and change your password.

MD;

    if (!file_exists('content/index.md')) {
        file_put_contents('content/index.md', $sample_content);
        echo "✓ Created sample content: content/index.md\n";
    } else {
        echo "  File already exists: content/index.md\n";
    }

    // Create empty menu files
    $menus = ['header-menu', 'left-menu', 'right-menu'];
    $default_header_menu = [
        ['label' => 'Home', 'url' => '/'],
    ];

    foreach ($menus as $menu) {
        $menu_file = "config/$menu.json";
        if (!file_exists($menu_file)) {
            $menu_data = ($menu === 'header-menu') ? $default_header_menu : [];
            file_put_contents($menu_file, json_encode($menu_data, JSON_PRETTY_PRINT));
            echo "✓ Created menu: $menu_file\n";
        } else {
            echo "  File already exists: $menu_file\n";
        }
    }

    // Create default admin user
    echo "\n";
    if (!file_exists('config/users.json')) {
        echo "Creating default admin user...\n";
        echo "Username: admin\n";
        echo "Password: ";

        // Read password from stdin
        system('stty -echo');
        $password = trim(fgets(STDIN));
        system('stty echo');
        echo "\n";

        if (strlen($password) < 8) {
            echo "✗ Error: Password must be at least 8 characters\n";
            exit(1);
        }

        if (auth_create_user('admin', $password, 'admin')) {
            echo "✓ Created admin user\n";
        } else {
            echo "✗ Error: Failed to create admin user\n";
            exit(1);
        }
    } else {
        echo "  Users file already exists: config/users.json\n";
    }

    echo "\n";
    echo "╔════════════════════════════════════════╗\n";
    echo "║    INITIALIZATION COMPLETE             ║\n";
    echo "╚════════════════════════════════════════╝\n";
    echo "\n";
    echo "Next steps:\n";
    echo "1. Start the Docker container: docker-compose up -d\n";
    echo "2. Visit http://localhost:8080 to view your site\n";
    echo "3. Log in to admin panel at http://localhost:8080/admin.php\n";
    echo "4. Edit content files in the /content directory\n";
    echo "\n";
}

/**
 * Create a new user
 */
function command_create_user(array $args) {
    if (count($args) < 2) {
        echo "Usage: php admin-tools.php create-user <username> <role>\n";
        echo "Roles: admin, editor\n";
        exit(1);
    }

    $username = $args[0];
    $role = $args[1];

    if (!in_array($role, ['admin', 'editor'])) {
        echo "✗ Error: Invalid role. Must be 'admin' or 'editor'\n";
        exit(1);
    }

    echo "Creating user: $username ($role)\n";
    echo "Password: ";

    // Read password from stdin
    system('stty -echo');
    $password = trim(fgets(STDIN));
    system('stty echo');
    echo "\n";

    if (auth_create_user($username, $password, $role)) {
        echo "✓ User created successfully\n";
    } else {
        echo "✗ Error: Failed to create user. User may already exist or password is too short.\n";
        exit(1);
    }
}

/**
 * Reset user password
 */
function command_reset_password(array $args) {
    if (count($args) < 1) {
        echo "Usage: php admin-tools.php reset-password <username>\n";
        exit(1);
    }

    $username = $args[0];

    echo "Resetting password for: $username\n";
    echo "New password: ";

    // Read password from stdin
    system('stty -echo');
    $password = trim(fgets(STDIN));
    system('stty echo');
    echo "\n";

    if (auth_reset_password($username, $password)) {
        echo "✓ Password reset successfully\n";
    } else {
        echo "✗ Error: Failed to reset password. User may not exist or password is too short.\n";
        exit(1);
    }
}

/**
 * List all users
 */
function command_list_users() {
    $users = auth_load_users();

    if (empty($users)) {
        echo "No users found.\n";
        echo "Create a user with: php admin-tools.php create-user <username> <role>\n";
        return;
    }

    echo "Users:\n";
    echo str_repeat('-', 50) . "\n";
    printf("%-20s %-10s\n", "Username", "Role");
    echo str_repeat('-', 50) . "\n";

    foreach ($users as $username => $user_data) {
        printf("%-20s %-10s\n", $username, $user_data['role']);
    }

    echo str_repeat('-', 50) . "\n";
    echo "Total: " . count($users) . " user(s)\n";
}

/**
 * Display help information
 */
function command_help() {
    echo "Available commands:\n\n";

    echo "  init\n";
    echo "    Initialize a fresh Relay installation\n";
    echo "    Creates directory structure, sample content, and default admin user\n\n";

    echo "  create-user <username> <role>\n";
    echo "    Create a new user\n";
    echo "    Roles: admin, editor\n";
    echo "    Example: php admin-tools.php create-user john editor\n\n";

    echo "  reset-password <username>\n";
    echo "    Reset a user's password\n";
    echo "    Example: php admin-tools.php reset-password admin\n\n";

    echo "  list-users\n";
    echo "    List all users\n\n";

    echo "  help\n";
    echo "    Display this help information\n\n";
}
