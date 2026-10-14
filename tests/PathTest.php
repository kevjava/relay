<?php

declare(strict_types=1);

require_once __DIR__ . '/Support/FilesystemTestCase.php';

final class PathTest extends FilesystemTestCase
{
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->temporaryDirectory = sys_get_temp_dir() . '/relay-path-' . bin2hex(random_bytes(8));
        $this->trackPathForCleanup($this->temporaryDirectory);
        $this->ensureDirectory($this->temporaryDirectory);
    }

    public function testIdentifierValidationAcceptsOnlySupportedCharacters(): void
    {
        $this->assertTrue(relay_path_is_valid_identifier('theme_2026-beta'));
        $this->assertTrue(relay_path_is_valid_identifier('A1'));
        $this->assertFalse(relay_path_is_valid_identifier(''));
        $this->assertFalse(relay_path_is_valid_identifier('nested/path'));
        $this->assertFalse(relay_path_is_valid_identifier('../escape'));
        $this->assertFalse(relay_path_is_valid_identifier('has space'));
        $this->assertFalse(relay_path_is_valid_identifier("null\0byte"));
    }

    public function testResolveWithinReturnsCanonicalPathsInsideAllowedDirectory(): void
    {
        $allowedDirectory = $this->temporaryDirectory . '/allowed';
        $nestedFile = $allowedDirectory . '/nested/file.txt';
        $this->writeFile($nestedFile, 'inside');

        $this->assertSame(realpath($allowedDirectory), relay_path_resolve_within($allowedDirectory, $allowedDirectory));
        $this->assertSame(realpath($nestedFile), relay_path_resolve_within($allowedDirectory, $nestedFile));
    }

    public function testResolveWithinRejectsMissingAndOutsidePaths(): void
    {
        $allowedDirectory = $this->temporaryDirectory . '/allowed';
        $outsideFile = $this->temporaryDirectory . '/outside.txt';
        $this->ensureDirectory($allowedDirectory);
        $this->writeFile($outsideFile, 'outside');

        $this->assertFalse(relay_path_resolve_within($this->temporaryDirectory . '/missing', $outsideFile));
        $this->assertFalse(relay_path_resolve_within($allowedDirectory, $allowedDirectory . '/missing.txt'));
        $this->assertFalse(relay_path_resolve_within($allowedDirectory, $outsideFile));
    }

    public function testResolveWithinRejectsSiblingPrefixCollision(): void
    {
        $allowedDirectory = $this->temporaryDirectory . '/allowed';
        $siblingFile = $this->temporaryDirectory . '/allowed-escape/file.txt';
        $this->ensureDirectory($allowedDirectory);
        $this->writeFile($siblingFile, 'outside');

        $this->assertFalse(relay_path_resolve_within($allowedDirectory, $siblingFile));
    }

    public function testResolveWithinRejectsSymlinkEscape(): void
    {
        if (!function_exists('symlink')) {
            $this->markTestSkipped('Symbolic links are not supported by this PHP runtime.');
        }

        $allowedDirectory = $this->temporaryDirectory . '/allowed';
        $outsideFile = $this->temporaryDirectory . '/outside.txt';
        $linkedFile = $allowedDirectory . '/linked.txt';
        $this->ensureDirectory($allowedDirectory);
        $this->writeFile($outsideFile, 'outside');

        if (!symlink($outsideFile, $linkedFile)) {
            $this->markTestSkipped('The test environment does not permit symbolic links.');
        }

        $this->assertFalse(relay_path_resolve_within($allowedDirectory, $linkedFile));
    }
}
