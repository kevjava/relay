<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DependencyTest extends TestCase
{
    public function testLibrariesLoadDirectlyWithTheirDeclaredDependencies(): void
    {
        $libraries = [
            'auth.php' => 'auth_login',
            'menu.php' => 'menu_render',
            'theme.php' => 'theme_get_active',
            'content.php' => 'content_load',
        ];

        foreach ($libraries as $library => $function) {
            $path = dirname(__DIR__) . '/lib/' . $library;
            $output = $this->runPhp("require_once " . var_export($path, true) . "; echo function_exists(" . var_export($function, true) . ") ? 'loaded' : 'missing';");

            $this->assertSame('loaded', $output, $library);
        }
    }

    public function testComposerAutoloadDoesNotPreloadRelayLibraries(): void
    {
        $autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';
        $output = $this->runPhp("require_once " . var_export($autoloadPath, true) . "; echo function_exists('auth_login') ? 'preloaded' : 'third-party-only';");

        $this->assertSame('third-party-only', $output);
    }

    public function testContentParsingWorksWhenCallerLoadsComposer(): void
    {
        $autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';
        $contentPath = dirname(__DIR__) . '/lib/content.php';
        $script = 'require_once ' . var_export($autoloadPath, true) . ';'
            . 'require_once ' . var_export($contentPath, true) . ';'
            . "echo is_array(content_load('index')) ? 'parsed' : 'failed';";

        $this->assertSame('parsed', $this->runPhp($script));
    }

    private function runPhp(string $script): string
    {
        $command = escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script);
        $output = shell_exec($command);

        return trim((string) $output);
    }
}
