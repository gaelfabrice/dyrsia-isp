<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Csrf;

/**
 * @covers \Csrf
 */
class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        $GLOBALS['config'] = ['csrf_enabled' => 'yes'];
        $GLOBALS['isApi'] = false;
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        unset($GLOBALS['config'], $GLOBALS['isApi']);
    }

    public function testGenerateTokenHasExpectedHexLength(): void
    {
        $token = Csrf::generateToken(16);
        $this->assertSame(32, strlen($token));
        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $token);
    }

    public function testGenerateTokenRespectsCustomLength(): void
    {
        $this->assertSame(8, strlen(Csrf::generateToken(4)));
    }

    public function testValidateToken(): void
    {
        $this->assertTrue(Csrf::validateToken('abc', 'abc'));
        $this->assertFalse(Csrf::validateToken('abc', 'xyz'));
    }

    public function testGenerateAndStoreTokenPopulatesSession(): void
    {
        $token = Csrf::generateAndStoreToken();
        $this->assertSame($token, $_SESSION['csrf_token']);
        $this->assertArrayHasKey('csrf_token_time', $_SESSION);
    }

    public function testGetTokenReusesExistingValidToken(): void
    {
        $first = Csrf::getToken();
        $second = Csrf::getToken();
        $this->assertSame($first, $second);
    }

    public function testGetTokenRegeneratesExpiredToken(): void
    {
        $_SESSION['csrf_token'] = 'old';
        $_SESSION['csrf_token_time'] = time() - 4000;
        $token = Csrf::getToken();
        $this->assertNotSame('old', $token);
    }

    public function testCheckReturnsTrueForValidToken(): void
    {
        $token = Csrf::generateAndStoreToken();
        $this->assertTrue(Csrf::check($token));
    }

    public function testCheckReturnsFalseForInvalidToken(): void
    {
        Csrf::generateAndStoreToken();
        $this->assertFalse(Csrf::check('wrong-token'));
    }

    public function testCheckReturnsFalseAndClearsExpiredToken(): void
    {
        $_SESSION['csrf_token'] = 'tok';
        $_SESSION['csrf_token_time'] = time() - 4000;
        $this->assertFalse(Csrf::check('tok'));
        $this->assertArrayNotHasKey('csrf_token', $_SESSION);
    }

    public function testCheckBypassedWhenDisabled(): void
    {
        $GLOBALS['config']['csrf_enabled'] = 'no';
        $this->assertTrue(Csrf::check('anything'));
    }

    public function testCheckBypassedForApiRequests(): void
    {
        $GLOBALS['isApi'] = true;
        $this->assertTrue(Csrf::check('anything'));
    }

    public function testClearTokenRemovesSessionEntries(): void
    {
        Csrf::generateAndStoreToken();
        Csrf::clearToken();
        $this->assertArrayNotHasKey('csrf_token', $_SESSION);
        $this->assertArrayNotHasKey('csrf_token_time', $_SESSION);
    }
}
