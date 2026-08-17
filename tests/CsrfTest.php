<?php

declare(strict_types=1);

require_once __DIR__ . '/Support/SessionTestCase.php';

final class CsrfTest extends SessionTestCase
{
    public function testGenerateTokenStoresItInSessionAndValidationSucceeds(): void
    {
        $token = csrf_generate_token();

        $this->assertSame(64, strlen($token));
        $this->assertSame($token, $_SESSION['csrf_token']);
        $this->assertArrayHasKey('csrf_token_time', $_SESSION);
        $this->assertTrue(csrf_validate_token($token));
    }

    public function testMissingTokenReportsMissingReason(): void
    {
        $this->assertFalse(csrf_validate_token('missing-token'));
        $this->assertSame('missing', csrf_get_validation_failure_reason('missing-token'));
        $this->assertSame(
            'Security token is missing. Please try again.',
            csrf_get_error_message('missing')
        );
    }

    public function testInvalidAndExpiredTokensAreReportedCorrectly(): void
    {
        $token = csrf_generate_token();

        $this->assertFalse(csrf_validate_token('wrong-token'));
        $this->assertSame('invalid', csrf_get_validation_failure_reason('wrong-token'));
        $this->assertSame(
            'Invalid security token. Please refresh the page and try again.',
            csrf_get_error_message('invalid')
        );

        $_SESSION['csrf_token_time'] = time() - 7201;

        $this->assertFalse(csrf_validate_token($token));
        $this->assertSame('expired', csrf_get_validation_failure_reason($token));
        $this->assertSame(
            'Your security token has expired. Please refresh the page and try again.',
            csrf_get_error_message('expired')
        );
    }

    public function testDetailedValidationReturnsReasonCodes(): void
    {
        $token = csrf_generate_token();

        $this->assertSame(
            ['valid' => true, 'reason' => 'valid'],
            csrf_validate_token_detailed($token)
        );
        $this->assertSame(
            ['valid' => false, 'reason' => 'invalid'],
            csrf_validate_token_detailed('incorrect-token')
        );
    }

    public function testTokenFieldAndMetaReuseCurrentToken(): void
    {
        $token = csrf_generate_token();

        $field = csrf_token_field();
        $meta = csrf_token_meta();

        $this->assertStringContainsString('name="csrf_token"', $field);
        $this->assertStringContainsString($token, $field);
        $this->assertStringContainsString('name="csrf-token"', $meta);
        $this->assertStringContainsString($token, $meta);
    }

    public function testGetTokenGeneratesAndReusesSessionToken(): void
    {
        $firstToken = csrf_get_token();
        $secondToken = csrf_get_token();

        $this->assertSame($firstToken, $secondToken);
        $this->assertSame($firstToken, $_SESSION['csrf_token']);
        $this->assertSame(
            'Security validation failed. Please try again.',
            csrf_get_error_message('unexpected')
        );
    }
}
