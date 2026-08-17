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
        $this->assertSame('/about', url_strip_base_path('/relay/about'));
        $this->assertSame('/outside', url_strip_base_path('/outside'));
    }
}
