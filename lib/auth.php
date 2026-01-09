<?php
/**
 * Authentication Library
 *
 * Provides user authentication, session management, and password handling
 * with security features including rate limiting and secure password hashing.
 */

// Define constants
define('RELAY_USERS_FILE', __DIR__ . '/../config/users.json');
define('RELAY_SESSION_TIMEOUT', 1800); // 30 minutes in seconds
define('RELAY_MAX_LOGIN_ATTEMPTS', 5);
define('RELAY_LOGIN_LOCKOUT_TIME', 900); // 15 minutes in seconds

/**
 * Initialize secure session configuration
 */
function auth_init_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', '1');

        // Set secure flag if using HTTPS
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', '1');
        }

        session_start();
    }
}

/**
 * Load users from JSON file
 *
 * @return array User data
 */
function auth_load_users(): array {
    if (!file_exists(RELAY_USERS_FILE)) {
        return [];
    }

    $json = file_get_contents(RELAY_USERS_FILE);
    $users = json_decode($json, true);

    return is_array($users) ? $users : [];
}

/**
 * Save users to JSON file
 *
 * @param array $users User data to save
 * @return bool Success status
 */
function auth_save_users(array $users): bool {
    $json = json_encode($users, JSON_PRETTY_PRINT);
    return file_put_contents(RELAY_USERS_FILE, $json, LOCK_EX) !== false;
}

/**
 * Validate username format
 *
 * @param string $username Username to validate
 * @return bool True if valid
 */
function auth_validate_username(string $username): bool {
    // Only allow alphanumeric characters and underscore
    return preg_match('/^[a-zA-Z0-9_]+$/', $username) === 1;
}

/**
 * Check and update rate limiting
 *
 * @return bool True if login attempt is allowed, false if rate limited
 */
function auth_check_rate_limit(): bool {
    auth_init_session();

    $now = time();

    // Initialize rate limit tracking if not exists
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
        $_SESSION['login_locked_until'] = 0;
    }

    // Check if currently locked out
    if (isset($_SESSION['login_locked_until']) && $_SESSION['login_locked_until'] > $now) {
        return false;
    }

    // Clean up old attempts (older than lockout time)
    $_SESSION['login_attempts'] = array_filter(
        $_SESSION['login_attempts'],
        fn($timestamp) => $timestamp > ($now - RELAY_LOGIN_LOCKOUT_TIME)
    );

    // Check if too many attempts
    if (count($_SESSION['login_attempts']) >= RELAY_MAX_LOGIN_ATTEMPTS) {
        $_SESSION['login_locked_until'] = $now + RELAY_LOGIN_LOCKOUT_TIME;
        return false;
    }

    return true;
}

/**
 * Record a failed login attempt
 */
function auth_record_failed_attempt(): void {
    auth_init_session();

    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }

    $_SESSION['login_attempts'][] = time();
}

/**
 * Get remaining lockout time in seconds
 *
 * @return int Seconds until lockout expires, 0 if not locked
 */
function auth_get_lockout_time(): int {
    auth_init_session();

    if (!isset($_SESSION['login_locked_until'])) {
        return 0;
    }

    $remaining = $_SESSION['login_locked_until'] - time();
    return max(0, $remaining);
}

/**
 * Authenticate a user with username and password
 *
 * @param string $username Username
 * @param string $password Password
 * @return bool True if authentication successful
 */
function auth_login(string $username, string $password): bool {
    auth_init_session();

    // Check rate limiting
    if (!auth_check_rate_limit()) {
        return false;
    }

    // Validate username format
    if (!auth_validate_username($username)) {
        auth_record_failed_attempt();
        return false;
    }

    // Load users
    $users = auth_load_users();

    // Check if user exists
    if (!isset($users[$username])) {
        auth_record_failed_attempt();
        return false;
    }

    $user = $users[$username];

    // Verify password
    if (!isset($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
        auth_record_failed_attempt();
        return false;
    }

    // Authentication successful - regenerate session ID
    session_regenerate_id(true);

    // Clear rate limiting
    $_SESSION['login_attempts'] = [];
    $_SESSION['login_locked_until'] = 0;

    // Set session data
    $_SESSION['user_authenticated'] = true;
    $_SESSION['user_username'] = $username;
    $_SESSION['user_role'] = $user['role'] ?? 'editor';
    $_SESSION['user_login_time'] = time();
    $_SESSION['user_last_activity'] = time();

    return true;
}

/**
 * Log out the current user
 */
function auth_logout(): void {
    auth_init_session();

    // Clear all session data
    $_SESSION = [];

    // Delete the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    // Destroy the session
    session_destroy();
}

/**
 * Check if current session is valid and authenticated
 *
 * @return bool True if authenticated and session valid
 */
function auth_check(): bool {
    auth_init_session();

    // Check if user is authenticated
    if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
        return false;
    }

    // Check session timeout
    if (isset($_SESSION['user_last_activity'])) {
        $inactive_time = time() - $_SESSION['user_last_activity'];

        if ($inactive_time > RELAY_SESSION_TIMEOUT) {
            auth_logout();
            return false;
        }
    }

    // Update last activity time
    $_SESSION['user_last_activity'] = time();

    return true;
}

/**
 * Get current authenticated user data
 *
 * @return array|null User data (username, role) or null if not authenticated
 */
function auth_get_user(): ?array {
    if (!auth_check()) {
        return null;
    }

    return [
        'username' => $_SESSION['user_username'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'editor',
        'login_time' => $_SESSION['user_login_time'] ?? 0,
    ];
}

/**
 * Require authentication, redirect to login page if not authenticated
 *
 * @param string $redirect_url URL to redirect to after login (default: current page)
 */
function auth_require_login(string $redirect_url = ''): void {
    if (!auth_check()) {
        // Store intended destination
        if (empty($redirect_url)) {
            $redirect_url = $_SERVER['REQUEST_URI'] ?? url_base('/admin.php');
        }

        $_SESSION['auth_redirect_after_login'] = $redirect_url;

        // Redirect to admin login
        header('Location: ' . url_base('/admin.php?action=login'));
        exit;
    }
}

/**
 * Check if current user has admin role
 *
 * @return bool True if user is admin
 */
function auth_is_admin(): bool {
    $user = auth_get_user();
    return $user !== null && ($user['role'] === 'admin');
}

/**
 * Change user password
 *
 * @param string $username Username
 * @param string $old_password Current password (required for current user)
 * @param string $new_password New password
 * @return bool True if password changed successfully
 */
function auth_change_password(string $username, string $old_password, string $new_password): bool {
    // Validate username
    if (!auth_validate_username($username)) {
        return false;
    }

    // Validate new password strength (minimum 8 characters)
    if (strlen($new_password) < 8) {
        return false;
    }

    // Load users
    $users = auth_load_users();

    // Check if user exists
    if (!isset($users[$username])) {
        return false;
    }

    // Verify old password
    if (!password_verify($old_password, $users[$username]['password_hash'])) {
        return false;
    }

    // Hash new password
    $new_hash = auth_hash_password($new_password);

    if ($new_hash === false) {
        return false;
    }

    // Update password
    $users[$username]['password_hash'] = $new_hash;

    // Save users
    return auth_save_users($users);
}

/**
 * Hash a password using the best available algorithm
 *
 * @param string $password Password to hash
 * @return string|false Hashed password or false on failure
 */
function auth_hash_password(string $password): string|false {
    // Try ARGON2ID first (preferred), fallback to BCRYPT
    if (defined('PASSWORD_ARGON2ID')) {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Create a new user (for CLI tools)
 *
 * @param string $username Username
 * @param string $password Password
 * @param string $role Role (admin or editor)
 * @return bool True if user created successfully
 */
function auth_create_user(string $username, string $password, string $role = 'editor'): bool {
    // Validate username
    if (!auth_validate_username($username)) {
        return false;
    }

    // Validate password
    if (strlen($password) < 8) {
        return false;
    }

    // Validate role
    if (!in_array($role, ['admin', 'editor'])) {
        return false;
    }

    // Load users
    $users = auth_load_users();

    // Check if user already exists
    if (isset($users[$username])) {
        return false;
    }

    // Hash password
    $password_hash = auth_hash_password($password);

    if ($password_hash === false) {
        return false;
    }

    // Create user
    $users[$username] = [
        'password_hash' => $password_hash,
        'role' => $role,
    ];

    // Save users
    return auth_save_users($users);
}

/**
 * Reset user password (for CLI tools)
 *
 * @param string $username Username
 * @param string $new_password New password
 * @return bool True if password reset successfully
 */
function auth_reset_password(string $username, string $new_password): bool {
    // Validate username
    if (!auth_validate_username($username)) {
        return false;
    }

    // Validate password
    if (strlen($new_password) < 8) {
        return false;
    }

    // Load users
    $users = auth_load_users();

    // Check if user exists
    if (!isset($users[$username])) {
        return false;
    }

    // Hash new password
    $password_hash = auth_hash_password($new_password);

    if ($password_hash === false) {
        return false;
    }

    // Update password
    $users[$username]['password_hash'] = $password_hash;

    // Save users
    return auth_save_users($users);
}
