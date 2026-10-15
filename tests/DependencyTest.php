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
            'path.php' => 'relay_path_resolve_within',
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

    public function testThemeMenuOverridesRemainAuthoritativeWhenCoreLoadsAfterward(): void
    {
        $overridePath = dirname(__DIR__) . '/themes/uswds/lib/menu.php';
        $corePath = dirname(__DIR__) . '/lib/menu.php';
        $menuData = var_export([
            [
                'label' => 'Docs & Guides',
                'url' => '/docs',
                'children' => [
                    ['label' => 'API "Reference"', 'url' => '/docs/api'],
                    ['label' => 'External', 'url' => 'https://example.test/docs'],
                    ['label' => 'Details', 'url' => '#details'],
                ],
            ],
            ['label' => 'Unsafe', 'url' => 'javascript:alert(1)'],
        ], true);
        $script = "\$_SERVER['SCRIPT_NAME'] = '/index.php';"
            . 'require_once ' . var_export($overridePath, true) . ';'
            . 'require_once ' . var_export($corePath, true) . ';'
            . '$sidebar = menu_render(' . $menuData . ", '/docs/api');"
            . '$header = menu_render_header(' . $menuData . ", '/docs/api');"
            . "echo str_contains(\$sidebar, 'usa-sidenav__sublist')"
            . " && str_contains(\$header, 'usa-nav__submenu')"
            . " && str_contains(\$header, 'API &quot;Reference&quot;')"
            . " && str_contains(\$sidebar, 'href=\"/relay/docs\"')"
            . " && str_contains(\$sidebar, 'href=\"https://example.test/docs\"')"
            . " && str_contains(\$sidebar, 'href=\"#details\"')"
            . " && str_contains(\$sidebar, 'href=\"#\"')"
            . " && str_contains(\$header, 'href=\"https://example.test/docs\"')"
            . " && str_contains(\$header, 'href=\"#details\"')"
            . " && str_contains(\$header, 'href=\"#\"')"
            . " && !str_contains(\$sidebar, 'javascript:')"
            . " && !str_contains(\$header, 'javascript:')"
            . " && str_contains(\$header, 'class=\"usa-current\"') ? 'override' : 'core';";

        $this->assertSame('override', $this->runPhp($script));
    }

    private function runPhp(string $script): string
    {
        $command = escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script);
        $output = shell_exec($command);

        return trim((string) $output);
    }
}
