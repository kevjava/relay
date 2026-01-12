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
 * Get detailed reason for CSRF token validation failure
 * Call this immediately after csrf_validate_token() returns false
 *
 * @param string $token The token that was validated
 * @return string Reason code: 'missing', 'expired', 'invalid', or 'valid'
 */
function csrf_get_validation_failure_reason(string $token): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['csrf_token'])) {
        return 'missing';
    }

    if (isset($_SESSION['csrf_token_time'])) {
        $token_age = time() - $_SESSION['csrf_token_time'];
        if ($token_age > 7200) { // 2 hours
            return 'expired';
        }
    }

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        return 'invalid';
    }

    return 'valid';
}

/**
 * Validate CSRF token and return detailed result
 * Alternative to csrf_validate_token() with more information
 *
 * @param string $token The token to validate
 * @return array Array with 'valid' (bool) and 'reason' (string)
 */
function csrf_validate_token_detailed(string $token): array {
    $valid = csrf_validate_token($token);

    if ($valid) {
        return ['valid' => true, 'reason' => 'valid'];
    }

    $reason = csrf_get_validation_failure_reason($token);
    return ['valid' => false, 'reason' => $reason];
}

/**
 * Get user-friendly error message for CSRF validation failure
 *
 * @param string $reason Reason code from csrf_get_validation_failure_reason()
 * @return string User-friendly error message
 */
function csrf_get_error_message(string $reason): string {
    switch ($reason) {
        case 'missing':
            return 'Security token is missing. Please try again.';
        case 'expired':
            return 'Your security token has expired. Please refresh the page and try again.';
        case 'invalid':
            return 'Invalid security token. Please refresh the page and try again.';
        default:
            return 'Security validation failed. Please try again.';
    }
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
