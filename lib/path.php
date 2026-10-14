<?php

/**
 * Validate a filesystem-safe identifier.
 */
function relay_path_is_valid_identifier(string $value): bool
{
    return preg_match('/^[a-zA-Z0-9_-]+$/', $value) === 1;
}

/**
 * Resolve an existing path and ensure it remains within an allowed directory.
 *
 * @return string|false Canonical path or false when missing or outside the directory
 */
function relay_path_resolve_within(string $allowed_directory, string $candidate_path): string|false
{
    $allowed_real_path = realpath($allowed_directory);
    $candidate_real_path = realpath($candidate_path);

    if ($allowed_real_path === false || !is_dir($allowed_real_path) || $candidate_real_path === false) {
        return false;
    }

    if ($candidate_real_path === $allowed_real_path) {
        return $candidate_real_path;
    }

    $allowed_prefix = str_ends_with($allowed_real_path, DIRECTORY_SEPARATOR)
        ? $allowed_real_path
        : $allowed_real_path . DIRECTORY_SEPARATOR;

    if (!str_starts_with($candidate_real_path, $allowed_prefix)) {
        return false;
    }

    return $candidate_real_path;
}
