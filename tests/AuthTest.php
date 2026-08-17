<?php

declare(strict_types=1);

require_once __DIR__ . '/Support/SessionTestCase.php';

final class AuthTest extends SessionTestCase
{
    private static ?string $originalUsersJson = null;
    private static bool $usersFileExisted = false;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$usersFileExisted = file_exists(RELAY_USERS_FILE);
        self::$originalUsersJson = self::$usersFileExisted ? file_get_contents(RELAY_USERS_FILE) : null;
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$usersFileExisted) {
            file_put_contents(RELAY_USERS_FILE, self::$originalUsersJson);
        } elseif (file_exists(RELAY_USERS_FILE)) {
            unlink(RELAY_USERS_FILE);
        }

        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->writeUsers([
            'admin' => [
                'password_hash' => auth_hash_password('Secret123!'),
                'role' => 'admin',
            ],
            'editor_user' => [
                'password_hash' => auth_hash_password('Editor123!'),
                'role' => 'editor',
            ],
        ]);
    }

    public function testAuthInitSessionAppliesSecureDefaults(): void
    {
        auth_init_session();

        $this->assertSame(PHP_SESSION_ACTIVE, session_status());
        $this->assertSame('1', ini_get('session.cookie_httponly'));
        $this->assertSame('Strict', ini_get('session.cookie_samesite'));
        $this->assertSame('1', ini_get('session.use_strict_mode'));
    }

    public function testAuthInitSessionSetsSecureCookieWhenHttpsIsEnabled(): void
    {
        $_SERVER['HTTPS'] = 'on';

        auth_init_session();

        $this->assertSame('1', ini_get('session.cookie_secure'));
    }

    public function testSuccessfulLoginPopulatesSessionAndLogoutClearsIt(): void
    {
        $this->assertTrue(auth_login('admin', 'Secret123!'));
        $this->assertTrue(auth_check());

        $user = auth_get_user();

        $this->assertSame('admin', $user['username']);
        $this->assertSame('admin', $user['role']);
        $this->assertTrue(auth_is_admin());

        auth_logout();

        $this->assertFalse(auth_check());
        $this->assertNull(auth_get_user());
        $this->assertFalse(auth_is_admin());
    }

    public function testInvalidLoginRecordsAttemptsAndEventuallyLocksOut(): void
    {
        $this->assertFalse(auth_login('invalid user', 'bad-password'));
        $this->assertCount(1, $_SESSION['login_attempts']);

        $_SESSION['login_attempts'] = array_fill(0, RELAY_MAX_LOGIN_ATTEMPTS, time());
        $_SESSION['login_locked_until'] = 0;

        $this->assertFalse(auth_check_rate_limit());
        $this->assertGreaterThan(0, auth_get_lockout_time());
    }

    /**
     * 
     */
    public function testSessionTimeoutExpiresAuthentication(): void
    {
        $this->assertTrue(auth_login('admin', 'Secret123!'));

        $_SESSION['user_last_activity'] = time() - RELAY_SESSION_TIMEOUT - 1;

        $this->assertFalse(auth_check());
        $this->assertTrue($_SESSION['auth_session_expired']);
        $this->assertArrayNotHasKey('user_authenticated', $_SESSION);
    }

    public function testChangePasswordUpdatesStoredHash(): void
    {
        $this->assertTrue(auth_change_password('admin', 'Secret123!', 'NewSecret123!'));
        $this->assertFalse(auth_change_password('admin', 'Secret123!', 'AnotherSecret123!'));

        $users = auth_load_users();

        $this->assertTrue(password_verify('NewSecret123!', $users['admin']['password_hash']));
        $this->assertFalse(password_verify('Secret123!', $users['admin']['password_hash']));
    }

    public function testCreateUserAndResetPasswordPersistChanges(): void
    {
        $this->assertTrue(auth_create_user('new_user', 'Created123!', 'editor'));
        $this->assertFalse(auth_create_user('new_user', 'Created123!', 'editor'));
        $this->assertFalse(auth_create_user('bad-user', 'Created123!', 'editor'));
        $this->assertFalse(auth_create_user('badrole', 'Created123!', 'manager'));

        $users = auth_load_users();
        $this->assertArrayHasKey('new_user', $users);
        $this->assertSame('editor', $users['new_user']['role']);

        $oldHash = $users['editor_user']['password_hash'];

        $this->assertTrue(auth_reset_password('editor_user', 'Updated123!'));
        $this->assertFalse(auth_reset_password('missing_user', 'Updated123!'));

        $updatedUsers = auth_load_users();
        $this->assertNotSame($oldHash, $updatedUsers['editor_user']['password_hash']);
        $this->assertTrue(password_verify('Updated123!', $updatedUsers['editor_user']['password_hash']));
    }

    public function testFlashMessagesCanBeCheckedRetrievedAndExpire(): void
    {
        $this->assertFalse(auth_has_flash_message());

        auth_set_flash_message('Saved successfully.', 'success');

        $this->assertTrue(auth_has_flash_message());

        $flash = auth_get_flash_message();

        $this->assertSame([
            'message' => 'Saved successfully.',
            'type' => 'success',
        ], $flash);
        $this->assertFalse(auth_has_flash_message());
        $this->assertNull(auth_get_flash_message());

        auth_set_flash_message('Expired message', 'warning');
        $_SESSION['auth_flash_message']['timestamp'] = time() - 301;

        $this->assertNull(auth_get_flash_message());
    }

    public function testValidateUsernameAndHashPasswordHelpers(): void
    {
        $this->assertTrue(auth_validate_username('user_name_123'));
        $this->assertFalse(auth_validate_username('bad-user'));

        $hash = auth_hash_password('Secret123!');

        $this->assertIsString($hash);
        $this->assertTrue(password_verify('Secret123!', $hash));
    }

    private function writeUsers(array $users): void
    {
        $dir = dirname(RELAY_USERS_FILE);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents(RELAY_USERS_FILE, json_encode($users, JSON_PRETTY_PRINT));
    }
}
