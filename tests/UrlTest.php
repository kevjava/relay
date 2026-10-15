<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class UrlTest extends TestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testUrlHelpersWorkForRootDeployment(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $this->assertSame('', url_get_base_path());
        $this->assertSame('/about', url_base('/about'));
        $this->assertSame('/', url_base('/'));
        $this->assertSame('/docs', url_strip_base_path('/docs'));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testUrlHelpersWorkForSubdirectoryDeployment(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/relay/index.php';

        $this->assertSame('/relay', url_get_base_path());
        $this->assertSame('/relay/about', url_base('/about'));
        $this->assertSame('/relay/', url_base('/'));
        $this->assertSame('/relay/docs#overview', url_base('/docs#overview'));
        $this->assertSame('#overview', url_base('#overview'));
        $this->assertSame('https://example.test/docs', url_base('https://example.test/docs'));
        $this->assertSame('mailto:hello@example.test', url_base('mailto:hello@example.test'));
        $this->assertSame('//cdn.example.test/app.js', url_base('//cdn.example.test/app.js'));
        $this->assertSame('#', url_base('javascript:alert(1)'));
        $this->assertSame('#', url_base('data:text/html,unsafe'));
        $this->assertSame('#', url_base('vbscript:unsafe'));
        $this->assertSame('/about', url_strip_base_path('/relay/about'));
        $this->assertSame('/outside', url_strip_base_path('/outside'));
    }
}
