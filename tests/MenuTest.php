<?php

declare(strict_types=1);

require_once __DIR__ . '/Support/FilesystemTestCase.php';

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
}
