<?php

/**
 * Load an array from a JSON file.
 *
 * @return array|false Decoded data or false when the file cannot be loaded.
 */
function relay_json_load(string $file_path): array|false
{
    if (!file_exists($file_path)) {
        return false;
    }

    try {
        set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });
        $json = file_get_contents($file_path);
        restore_error_handler();
    } catch (ErrorException $exception) {
        restore_error_handler();
        error_log("Relay CMS: Failed to read JSON file: {$file_path} ({$exception->getMessage()})");
        return false;
    }

    if ($json === false) {
        error_log("Relay CMS: Failed to read JSON file: {$file_path}");
        return false;
    }

    try {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        error_log("Relay CMS: Failed to decode JSON file: {$file_path} ({$exception->getMessage()})");
        return false;
    }

    if (!is_array($data)) {
        error_log("Relay CMS: JSON file must contain an array: {$file_path}");
        return false;
    }

    return $data;
}

/**
 * Save an array as a JSON file.
 */
function relay_json_save(string $file_path, array $data): bool
{
    $directory = dirname($file_path);

    if (!is_dir($directory)) {
        try {
            set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
                throw new ErrorException($message, 0, $severity, $file, $line);
            });
            $created = mkdir($directory, 0755, true);
            restore_error_handler();
        } catch (ErrorException $exception) {
            restore_error_handler();
            error_log("Relay CMS: Failed to create JSON directory: {$directory} ({$exception->getMessage()})");
            return false;
        }

        if (!$created && !is_dir($directory)) {
            error_log("Relay CMS: Failed to create JSON directory: {$directory}");
            return false;
        }
    }

    try {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        error_log("Relay CMS: Failed to encode JSON file: {$file_path} ({$exception->getMessage()})");
        return false;
    }

    try {
        set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });
        $written = file_put_contents($file_path, $json, LOCK_EX);
        restore_error_handler();
    } catch (ErrorException $exception) {
        restore_error_handler();
        error_log("Relay CMS: Failed to write JSON file: {$file_path} ({$exception->getMessage()})");
        return false;
    }

    if ($written === false) {
        error_log("Relay CMS: Failed to write JSON file: {$file_path}");
        return false;
    }

    return true;
}
