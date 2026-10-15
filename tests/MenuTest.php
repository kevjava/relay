<?php

declare(strict_types=1);

require_once __DIR__ . '/Support/FilesystemTestCase.php';

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

final class MenuTest extends FilesystemTestCase
{
    public function testMenuValidateRejectsMalformedStructures(): void
    {
        $this->assertTrue(menu_validate([['label' => 'Home', 'url' => '/']]));
        $this->assertFalse(menu_validate([['label' => 'Missing URL']]));
        $this->assertFalse(menu_validate([['label' => 'Home', 'url' => '/', 'children' => 'invalid']]));
        $this->assertFalse(menu_validate([['label' => 'Home', 'url' => '/', 'children' => [['label' => 42, 'url' => '/x']]]]));
    }

    public function testMenuSaveLoadAndListRoundTrip(): void
    {
        $menuName = 'phpunit-menu';
        $menuPath = RELAY_MENU_DIR . '/' . $menuName . '.json';
        $menuData = [
            ['label' => 'Home', 'url' => '/'],
            [
                'label' => 'Docs',
                'url' => '/docs',
                'children' => [
                    ['label' => 'API', 'url' => '/docs/api'],
                ],
            ],
        ];

        $this->trackPathForCleanup($menuPath);

        $this->assertTrue(menu_save($menuName, $menuData));
        $this->assertSame($menuData, menu_load($menuName));
        $this->assertContains($menuName, menu_list());
        $this->assertFalse(menu_save('../bad-menu', $menuData));
    }

    public function testMenuRenderingMarksActiveItemsAndPrefixesUrls(): void
    {
        $menuData = [
            ['label' => 'Home', 'url' => '/'],
            [
                'label' => 'Docs',
                'url' => '/docs',
                'children' => [
                    ['label' => 'API', 'url' => '/docs/api'],
                ],
            ],
        ];

        $rendered = menu_render($menuData, '/docs/api');
        $header = menu_render_header($menuData, '/docs');

        $this->assertTrue(menu_is_active('/docs', '/docs/api'));
        $this->assertFalse(menu_is_active('/blog', '/docs/api'));
        $this->assertStringContainsString('relay-menu-depth-0', $rendered);
        $this->assertStringContainsString('class="relay-menu-item active has-children"', $rendered);
        $this->assertStringContainsString('href="/docs/api"', $rendered);
        $this->assertStringContainsString('class="active"', $header);
        $this->assertSame('', menu_render([], '/docs'));
        $this->assertSame('', menu_render_header([], '/docs'));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testMenuRenderingPreservesExactMarkupForSubdirectoryDeployment(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/relay/index.php';
        $menuData = [
            ['label' => 'Home', 'url' => '/'],
            [
                'label' => 'Docs & <Guides>',
                'url' => '/docs',
                'children' => [
                    ['label' => 'API "Reference"', 'url' => '/docs/api'],
                ],
            ],
        ];

        $this->assertSame(
            '<ul class="relay-menu relay-menu-depth-0"><li class="relay-menu-item"><a href="/relay/">Home</a></li><li class="relay-menu-item active has-children"><a href="/relay/docs">Docs &amp; &lt;Guides&gt;</a><ul class="relay-menu relay-menu-depth-1"><li class="relay-menu-item active"><a href="/relay/docs/api">API &quot;Reference&quot;</a></li></ul></li></ul>',
            menu_render($menuData, '/docs/api')
        );
        $this->assertSame(
            '<nav class="relay-header-menu"><ul><li><a href="/relay/">Home</a></li><li class="active"><a href="/relay/docs">Docs &amp; &lt;Guides&gt;</a></li></ul></nav>',
            menu_render_header($menuData, '/docs/api')
        );
    }

    public function testMenuActiveStateHandlesRootTrailingSlashesAndPathBoundaries(): void
    {
        $this->assertTrue(menu_is_active('/', '/'));
        $this->assertFalse(menu_is_active('/', '/docs'));
        $this->assertTrue(menu_is_active('/docs/', '/docs'));
        $this->assertTrue(menu_is_active('/docs', '/docs/api'));
        $this->assertFalse(menu_is_active('/docs', '/docs-old'));
    }

    public function testPrepareRenderItemReturnsSharedRenderingContext(): void
    {
        $children = [['label' => 'API', 'url' => '/docs/api']];

        $this->assertSame(
            [
                'label' => 'Docs &amp; &lt;Guides&gt;',
                'url' => '/docs',
                'href' => '/docs',
                'is_active' => true,
                'children' => $children,
                'has_children' => true,
            ],
            menu_prepare_render_item(
                ['label' => 'Docs & <Guides>', 'url' => '/docs', 'children' => $children],
                '/docs/api'
            )
        );
        $this->assertSame(
            [],
            menu_prepare_render_item(
                ['label' => 'Home', 'url' => '/', 'children' => 'invalid'],
                '/docs'
            )['children']
        );
    }

    public function testMenuIndentHelpersConvertBetweenFlatAndNestedStructures(): void
    {
        $flat = [
            ['label' => 'Home', 'url' => '/', 'indent' => 0],
            ['label' => 'Docs', 'url' => '/docs', 'indent' => 0],
            ['label' => 'API', 'url' => '/docs/api', 'indent' => 1],
        ];

        $nested = menu_flatten_to_nested($flat);

        $this->assertCount(2, $nested);
        $this->assertSame('API', $nested[1]['children'][0]['label']);
        $this->assertSame($flat, menu_nested_to_flat($nested));
    }

    public function testDeepMenuConversionAndPersistencePreserveOrder(): void
    {
        $menuName = 'phpunit-deep-menu';
        $menuPath = RELAY_MENU_DIR . '/' . $menuName . '.json';
        $nested = [
            [
                'label' => 'Parent',
                'url' => '/parent',
                'children' => [
                    [
                        'label' => 'Child',
                        'url' => '/parent/child',
                        'children' => [
                            ['label' => 'First Grandchild', 'url' => '/parent/child/first'],
                            ['label' => 'Second Grandchild', 'url' => '/parent/child/second'],
                        ],
                    ],
                ],
            ],
            ['label' => 'Sibling', 'url' => '/sibling'],
        ];

        $this->trackPathForCleanup($menuPath);

        $flat = menu_nested_to_flat($nested);

        $this->assertSame([0, 1, 2, 2, 0], array_column($flat, 'indent'));
        $this->assertSame($nested, menu_flatten_to_nested($flat));
        $this->assertTrue(menu_save($menuName, $nested));
        $this->assertSame($nested, menu_load($menuName));
    }
}
