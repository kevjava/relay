<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

abstract class SessionTestCase extends TestCase
{
    private static string $sessionSavePath;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$sessionSavePath = sys_get_temp_dir() . '/relay-phpunit-sessions';

        if (!is_dir(self::$sessionSavePath)) {
            mkdir(self::$sessionSavePath, 0777, true);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->resetSessionState();

        ini_set('session.save_path', self::$sessionSavePath);
        ini_set('session.cookie_secure', '0');
        session_name('relay_test_session');
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTPS']);

        $this->resetSessionState();

        parent::tearDown();
    }

    protected function resetSessionState(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_unset();
            session_destroy();
        }

        $_SESSION = [];

        if (session_status() === PHP_SESSION_NONE) {
            session_id(bin2hex(random_bytes(8)));
        }
    }
}
