<?php
/**
 * CSRF Protection Library
 *
 * Provides token generation and validation for Cross-Site Request Forgery protection.
 * Tokens are stored in the session and validated on form submissions.
 */

/**
 * Generate a new CSRF token and store it in the session
 *
 * @return string The generated token
 */
function csrf_generate_token(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Generate a cryptographically secure random token
    $token = bin2hex(random_bytes(32));

    // Store in session
    $_SESSION['csrf_token'] = $token;
    $_SESSION['csrf_token_time'] = time();

    return $token;
}

/**
 * Validate a CSRF token against the session token
 *
 * @param string $token The token to validate
 * @return bool True if valid, false otherwise
 */
function csrf_validate_token(string $token): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Check if session token exists
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }

    // Check token age (expire after 2 hours)
    if (isset($_SESSION['csrf_token_time'])) {
        $token_age = time() - $_SESSION['csrf_token_time'];
        if ($token_age > 7200) { // 2 hours
            return false;
        }
    }

    // Use hash_equals to prevent timing attacks
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate an HTML hidden input field with CSRF token
 *
 * @return string HTML input field
 */
function csrf_token_field(): string {
    $token = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : csrf_generate_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Generate an HTML meta tag with CSRF token for AJAX requests
 *
 * @return string HTML meta tag
 */
function csrf_token_meta(): string {
    $token = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : csrf_generate_token();
    return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Get the current CSRF token (generate if not exists)
 *
 * @return string The current token
 */
function csrf_get_token(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    return isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : csrf_generate_token();
}
