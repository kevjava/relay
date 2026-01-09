<?php
/**
 * Relay - Admin Interface
 *
 * Provides authentication, dashboard, and menu management interface.
 */

// Load Composer autoloader and libraries
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/content.php';
require_once __DIR__ . '/lib/csrf.php';
require_once __DIR__ . '/lib/settings.php';
require_once __DIR__ . '/lib/theme.php';

// Load theme-specific menu library BEFORE core (allows theme to override)
theme_load_lib('menu');

// Load core menu library (only defines functions if not already defined by theme)
require_once __DIR__ . '/lib/menu.php';

// Start session
auth_init_session();

// Handle actions
$action = $_GET['action'] ?? 'dashboard';
$message = '';
$error = '';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Login action
    if ($action === 'login') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (auth_login($username, $password)) {
            // Redirect to dashboard or intended page
            $redirect = $_SESSION['auth_redirect_after_login'] ?? '/admin.php';
            unset($_SESSION['auth_redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $lockout_time = auth_get_lockout_time();
            if ($lockout_time > 0) {
                $error = 'Too many failed attempts. Please try again in ' . ceil($lockout_time / 60) . ' minutes.';
            } else {
                $error = 'Invalid username or password.';
            }
        }
    }

    // Change password action
    if ($action === 'change-password') {
        auth_require_login();

        if (!csrf_validate_token($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid CSRF token.';
        } else {
            $user = auth_get_user();
            $old_password = $_POST['old_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if ($new_password !== $confirm_password) {
                $error = 'New passwords do not match.';
            } elseif (strlen($new_password) < 8) {
                $error = 'Password must be at least 8 characters.';
            } elseif (auth_change_password($user['username'], $old_password, $new_password)) {
                $message = 'Password changed successfully.';
            } else {
                $error = 'Failed to change password. Check your current password.';
            }
        }
    }

    // Change theme action
    if ($action === 'change-theme') {
        auth_require_login();

        if (!csrf_validate_token($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid CSRF token.';
        } else {
            $new_theme = $_POST['theme'] ?? '';

            if (theme_validate($new_theme)) {
                if (theme_set_active($new_theme)) {
                    $message = 'Theme changed successfully to ' . htmlspecialchars($new_theme) . '.';
                } else {
                    $error = 'Failed to save theme setting.';
                }
            } else {
                $error = 'Invalid theme selected.';
            }
        }
    }

    // Save menu action (AJAX)
    if ($action === 'save-menu') {
        auth_require_login();

        // Clean any output buffers and start fresh to prevent corrupted JSON
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();

        header('Content-Type: application/json');

        if (!csrf_validate_token($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
            ob_end_flush();
            exit;
        }

        $menu_name = $_POST['menu_name'] ?? '';
        $menu_data_json = $_POST['menu_data'] ?? '';

        $menu_data = json_decode($menu_data_json, true);

        if ($menu_data === null) {
            echo json_encode(['success' => false, 'error' => 'Invalid menu data']);
            ob_end_flush();
            exit;
        }

        if (menu_save($menu_name, $menu_data)) {
            echo json_encode(['success' => true, 'message' => 'Menu saved successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save menu']);
        }
        ob_end_flush();
        exit;
    }
}

// Handle logout
if ($action === 'logout') {
    auth_logout();
    header('Location: /admin.php?action=login');
    exit;
}

// Require authentication for all actions except login
if ($action !== 'login') {
    auth_require_login();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relay Admin</title>
    <link rel="stylesheet" href="/assets/css/relay.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <?php echo csrf_token_meta(); ?>
</head>
<body class="relay-admin">

<?php if ($action === 'login'): ?>
    <!-- Login Page -->
    <div class="relay-admin-login">
        <div class="relay-admin-login-box">
            <h1>Relay Administration</h1>

            <?php if ($error): ?>
                <div class="relay-message relay-message-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="post" action="/admin.php?action=login">
                <div class="relay-form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>

                <div class="relay-form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="relay-button relay-button-primary">Log In</button>
            </form>
        </div>
    </div>

<?php else: ?>
    <!-- Authenticated Admin Interface -->
    <header class="relay-admin-header">
        <div class="relay-container">
            <div class="relay-admin-header-content">
                <h1><a href="/admin.php">Relay Admin</a></h1>
                <nav class="relay-admin-nav">
                    <a href="/" target="_blank">View Site</a>
                    <span class="relay-admin-user">
                        <?php
                        $user = auth_get_user();
    echo htmlspecialchars($user['username']);
    if (auth_is_admin()) {
        echo ' <span class="relay-badge">Admin</span>';
    }
    ?>
                    </span>
                    <a href="/admin.php?action=logout">Log Out</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="relay-admin-content">
        <div class="relay-container">
            <?php if ($message): ?>
                <div class="relay-message relay-message-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="relay-message relay-message-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($action === 'dashboard'): ?>
                <!-- Dashboard -->
                <h2>Dashboard</h2>

                <div class="relay-admin-grid">
                    <div class="relay-admin-card">
                        <h3>Menus</h3>
                        <p>Manage navigation menus for your site.</p>
                        <ul class="relay-menu-list">
                            <?php
        $menus = menu_list();
                if (empty($menus)): ?>
                                <li><em>No menus found</em></li>
                            <?php else:
                                foreach ($menus as $menu_name): ?>
                                    <li>
                                        <a href="/admin.php?action=edit-menu&menu=<?php echo urlencode($menu_name); ?>">
                                            <?php echo htmlspecialchars($menu_name); ?>
                                        </a>
                                    </li>
                                <?php endforeach;
                            endif; ?>
                        </ul>
                    </div>

                    <div class="relay-admin-card">
                        <h3>Change Password</h3>
                        <form method="post" action="/admin.php?action=change-password">
                            <?php echo csrf_token_field(); ?>

                            <div class="relay-form-group">
                                <label for="old_password">Current Password</label>
                                <input type="password" id="old_password" name="old_password" required>
                            </div>

                            <div class="relay-form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" required minlength="8">
                                <small>Minimum 8 characters</small>
                            </div>

                            <div class="relay-form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                            </div>

                            <button type="submit" class="relay-button relay-button-primary">Change Password</button>
                        </form>
                    </div>

                    <div class="relay-admin-card">
                        <h3>Users</h3>
                        <p>Current users in the system.</p>
                        <ul class="relay-user-list">
                            <?php
                            $users = auth_load_users();
foreach ($users as $username => $user_data): ?>
                                <li>
                                    <strong><?php echo htmlspecialchars($username); ?></strong>
                                    <span class="relay-badge"><?php echo htmlspecialchars($user_data['role']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <small>Use CLI tools to manage users: <code>php admin-tools.php</code></small>
                    </div>

                    <div class="relay-admin-card">
                        <h3>Theme Settings</h3>
                        <p>Select the active theme for your site.</p>

                        <?php
                        $available_themes = theme_list_available();
$active_theme = theme_get_active();
$active_metadata = theme_get_metadata($active_theme);
?>

                        <?php if (!empty($available_themes)): ?>
                            <form method="post" action="/admin.php?action=change-theme">
                                <?php echo csrf_token_field(); ?>

                                <div class="relay-form-group">
                                    <label for="theme">Active Theme</label>
                                    <select id="theme" name="theme" class="relay-select">
                                        <?php foreach ($available_themes as $theme_name):
                                            $metadata = theme_get_metadata($theme_name);
                                            $selected = ($theme_name === $active_theme) ? 'selected' : '';
                                            ?>
                                            <option value="<?php echo htmlspecialchars($theme_name); ?>" <?php echo $selected; ?>>
                                                <?php echo htmlspecialchars($metadata['name'] ?? $theme_name); ?>
                                                <?php if (isset($metadata['version'])): ?>
                                                    (v<?php echo htmlspecialchars($metadata['version']); ?>)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <?php if ($active_metadata): ?>
                                    <div class="relay-theme-info">
                                        <p><strong>Current:</strong> <?php echo htmlspecialchars($active_metadata['name']); ?></p>
                                        <?php if (isset($active_metadata['description'])): ?>
                                            <p><small><?php echo htmlspecialchars($active_metadata['description']); ?></small></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <button type="submit" class="relay-button relay-button-primary">Change Theme</button>
                            </form>
                        <?php else: ?>
                            <p class="relay-message relay-message-warning">No themes available. Create a theme in the <code>themes/</code> directory.</p>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($action === 'edit-menu'): ?>
                <!-- Menu Editor -->
                <?php
                $menu_name = $_GET['menu'] ?? '';
                $menu_data = menu_load($menu_name);
                $menu_flat = menu_nested_to_flat($menu_data);
                ?>

                <div class="relay-menu-editor-header">
                    <h2>Edit Menu: <?php echo htmlspecialchars($menu_name); ?></h2>
                    <a href="/admin.php" class="relay-button relay-button-secondary">Back to Dashboard</a>
                </div>

                <div class="relay-menu-editor">
                    <div class="relay-menu-editor-toolbar">
                        <button type="button" id="add-menu-item" class="relay-button relay-button-primary">Add Item</button>
                        <button type="button" id="save-menu" class="relay-button relay-button-success">Save Menu</button>
                        <span id="save-status"></span>
                    </div>

                    <div class="relay-menu-items" id="menu-items">
                        <?php if (empty($menu_flat)): ?>
                            <p class="relay-menu-empty">No menu items. Click "Add Item" to create one.</p>
                        <?php else: ?>
                            <?php foreach ($menu_flat as $index => $item): ?>
                                <div class="relay-menu-item" data-index="<?php echo $index; ?>" data-indent="<?php echo $item['indent']; ?>">
                                    <div class="relay-menu-item-controls">
                                        <button type="button" class="relay-button-icon move-up" title="Move Up">↑</button>
                                        <button type="button" class="relay-button-icon move-down" title="Move Down">↓</button>
                                        <button type="button" class="relay-button-icon indent-out" title="Outdent">←</button>
                                        <button type="button" class="relay-button-icon indent-in" title="Indent">→</button>
                                    </div>
                                    <div class="relay-menu-item-content" style="margin-left: <?php echo($item['indent'] * 30); ?>px;">
                                        <input type="text" class="menu-item-label" value="<?php echo htmlspecialchars($item['label']); ?>" placeholder="Label">
                                        <input type="text" class="menu-item-url" value="<?php echo htmlspecialchars($item['url']); ?>" placeholder="URL">
                                        <button type="button" class="relay-button relay-button-danger delete-item">Delete</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <input type="hidden" id="menu-name" value="<?php echo htmlspecialchars($menu_name); ?>">
                <script src="/assets/js/menu-editor.js"></script>

            <?php endif; ?>
        </div>
    </div>

    <footer class="relay-admin-footer">
        <div class="relay-container">
            <p>Relay CMS Administration</p>
        </div>
    </footer>

<?php endif; ?>

</body>
</html>
