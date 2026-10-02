<?php

declare(strict_types=1);

require_once __DIR__ . '/Support/FilesystemTestCase.php';

final class JsonTest extends FilesystemTestCase
{
    public function testLoadReturnsFalseForMissingMalformedAndNonArrayFiles(): void
    {
        $directory = sys_get_temp_dir() . '/relay-json-' . bin2hex(random_bytes(6));
        $this->trackPathForCleanup($directory);

        $this->assertFalse(relay_json_load($directory . '/missing.json'));

        $malformedPath = $directory . '/malformed.json';
        $this->writeFile($malformedPath, '{invalid');
        $this->assertFalse(relay_json_load($malformedPath));

        $scalarPath = $directory . '/scalar.json';
        $this->writeFile($scalarPath, '"not an array"');
        $this->assertFalse(relay_json_load($scalarPath));
    }

    public function testSaveCreatesDirectoriesAndUsesConsistentEncoding(): void
    {
        $path = sys_get_temp_dir() . '/relay-json-' . bin2hex(random_bytes(6)) . '/nested/data.json';
        $this->trackPathForCleanup(dirname(dirname($path)));

        $data = ['url' => '/docs/api', 'label' => 'API'];

        $this->assertTrue(relay_json_save($path, $data));
        $this->assertSame($data, relay_json_load($path));
        $this->assertStringContainsString('"/docs/api"', (string) file_get_contents($path));
    }

    public function testSaveReturnsFalseForEncodingAndWriteFailures(): void
    {
        $encodingPath = sys_get_temp_dir() . '/relay-json-' . bin2hex(random_bytes(6)) . '.json';
        $this->trackPathForCleanup($encodingPath);

        $this->assertFalse(relay_json_save($encodingPath, ['resource' => fopen('php://memory', 'rb')]));

        $directoryPath = sys_get_temp_dir() . '/relay-json-' . bin2hex(random_bytes(6));
        $this->ensureDirectory($directoryPath);
        $this->trackPathForCleanup($directoryPath);

        $this->assertFalse(relay_json_save($directoryPath, ['value' => 'cannot overwrite directory']));
    }
}
