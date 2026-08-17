<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

abstract class FilesystemTestCase extends TestCase
{
    /** @var array<string, string|false> */
    private array $fileBackups = [];

    /** @var list<string> */
    private array $cleanupPaths = [];

    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/';
    }

    protected function tearDown(): void
    {
        $this->cleanupTrackedPaths();
        $this->restoreBackedUpFiles();

        unset($_SERVER['SCRIPT_NAME'], $_SERVER['REQUEST_URI'], $_SERVER['HTTPS']);

        parent::tearDown();
    }

    protected function backupFile(string $path): void
    {
        if (array_key_exists($path, $this->fileBackups)) {
            return;
        }

        $this->fileBackups[$path] = file_exists($path) ? (string) file_get_contents($path) : false;
    }

    protected function trackPathForCleanup(string $path): void
    {
        $this->cleanupPaths[] = $path;
    }

    protected function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }

    protected function writeFile(string $path, string $contents): void
    {
        $this->ensureDirectory(dirname($path));
        file_put_contents($path, $contents);
    }

    protected function writeJsonFile(string $path, array $data): void
    {
        $this->writeFile($path, (string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function cleanupTrackedPaths(): void
    {
        foreach (array_reverse($this->cleanupPaths) as $path) {
            $this->deletePath($path);
        }

        $this->cleanupPaths = [];
    }

    private function restoreBackedUpFiles(): void
    {
        foreach ($this->fileBackups as $path => $contents) {
            if ($contents === false) {
                if (file_exists($path)) {
                    unlink($path);
                }

                continue;
            }

            $this->ensureDirectory(dirname($path));
            file_put_contents($path, $contents);
        }

        $this->fileBackups = [];
    }

    private function deletePath(string $path): void
    {
        if (!file_exists($path) && !is_link($path)) {
            return;
        }

        if (is_file($path) || is_link($path)) {
            unlink($path);
            return;
        }

        $entries = scandir($path);

        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $this->deletePath($path . DIRECTORY_SEPARATOR . $entry);
        }

        rmdir($path);
    }
}
