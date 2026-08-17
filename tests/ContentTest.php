<?php

declare(strict_types=1);

require_once __DIR__ . '/Support/FilesystemTestCase.php';

final class ContentTest extends FilesystemTestCase
{
    public function testSanitizePathHandlesValidAndInvalidInput(): void
    {
        $this->assertSame('index', content_sanitize_path(''));
        $this->assertSame('docs/getting-started', content_sanitize_path('/docs/getting-started/'));
        $this->assertFalse(content_sanitize_path('../secrets'));
        $this->assertFalse(content_sanitize_path('bad path'));
        $this->assertFalse(content_sanitize_path('docs/./page'));
    }

    public function testParseFrontmatterExtractsMetadataAndContent(): void
    {
        $parsed = content_parse_frontmatter("---\ntitle: Test Page\nauthor: Kevin\n---\n\n# Heading\n");

        $this->assertSame(
            [
                'title' => 'Test Page',
                'author' => 'Kevin',
            ],
            $parsed['metadata']
        );
        $this->assertSame('# Heading', $parsed['content']);
    }

    public function testGetFilePathPrefersDirectMarkdownFileOverDirectoryIndex(): void
    {
        $basePath = RELAY_CONTENT_DIR . '/phpunit-precedence';
        $directPath = $basePath . '.md';
        $nestedDir = $basePath;
        $nestedPath = $nestedDir . '/index.md';

        $this->trackPathForCleanup($nestedDir);
        $this->trackPathForCleanup($directPath);

        $this->writeFile($directPath, "---\ntitle: Direct\n---\n\nDirect content");
        $this->writeFile($nestedPath, "---\ntitle: Nested\n---\n\nNested content");

        $resolvedPath = content_get_file_path('phpunit-precedence');

        $this->assertSame(realpath($directPath), $resolvedPath);
        $this->assertTrue(content_exists('phpunit-precedence'));
    }

    public function testContentLoadRendersHtmlFromMarkdown(): void
    {
        $path = RELAY_CONTENT_DIR . '/phpunit-rendered.md';

        $this->trackPathForCleanup($path);
        $this->writeFile($path, "---\ntitle: Rendered Page\ndate: 2026-08-17\n---\n\n# Hello\n\nThis is **bold**.");

        $content = content_load('phpunit-rendered');

        $this->assertIsArray($content);
        $this->assertSame('Rendered Page', $content['metadata']['title']);
        $this->assertStringContainsString('<h1>Hello</h1>', $content['html']);
        $this->assertStringContainsString('<strong>bold</strong>', $content['html']);
        $this->assertSame('Rendered Page', content_get_title($content['metadata']));
        $this->assertSame('Fallback', content_get_title([], 'Fallback'));
        $this->assertFalse(content_load('../invalid'));
    }

    public function testContentListFilesReturnsRelativePathsForNestedFiles(): void
    {
        $rootFile = RELAY_CONTENT_DIR . '/phpunit-list-root.md';
        $nestedDir = RELAY_CONTENT_DIR . '/phpunit-list';
        $nestedFile = $nestedDir . '/child.md';

        $this->trackPathForCleanup($rootFile);
        $this->trackPathForCleanup($nestedDir);

        $this->writeFile($rootFile, '# Root');
        $this->writeFile($nestedFile, '# Child');

        $allFiles = content_list_files();
        $nestedFiles = content_list_files('phpunit-list');

        $this->assertContains('phpunit-list-root', $allFiles);
        $this->assertContains('phpunit-list/child', $allFiles);
        $this->assertSame(['phpunit-list/child'], $nestedFiles);
        $this->assertSame([], content_list_files('../invalid'));
    }
}
