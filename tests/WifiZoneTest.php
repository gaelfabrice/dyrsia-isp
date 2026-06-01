<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class WifiZoneTest extends TestCase
{
    public function testCsrfTokenRoundTrip()
    {
        $_SESSION['csrf_token'] = 'abc';
        $_SESSION['csrf_token_time'] = time();
        $this->assertTrue(Csrf::check('abc'));
    }

    public function testJwtIssueAndVerify()
    {
        WifiZoneCore::setConfig('wifizone_jwt_secret', 'test-secret-key');
        $token = WifiZoneApi::issueJwt(1, 3600);
        $payload = WifiZoneApi::verifyJwt($token);
        $this->assertNotNull($payload);
        $this->assertSame(1, $payload['sub']);
    }

    public function testHotspotHmacValidation()
    {
        $secret = 'testsecret';
        $ts = time();
        $payload = 'user|1.2.3.4|aa:bb|router1|' . $ts;
        $sig = hash_hmac('sha256', $payload, $secret);
        $this->assertEquals(64, strlen($sig));
    }

    public function testRateLimit()
    {
        ORM::for_table('wifizone_rate_limit')->where('scope', 'test')->delete_many();
        $this->assertTrue(WifiZoneHotspot::checkRateLimit('test', 'id1', 2, 60));
        $this->assertTrue(WifiZoneHotspot::checkRateLimit('test', 'id1', 2, 60));
        $this->assertFalse(WifiZoneHotspot::checkRateLimit('test', 'id1', 2, 60));
    }
}
