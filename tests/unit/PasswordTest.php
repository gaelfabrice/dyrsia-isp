<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Password;

/**
 * @covers \Password
 */
class PasswordTest extends TestCase
{
    public function testCryptProducesVerifiableBcryptHash(): void
    {
        $hash = Password::_crypt('secret');
        $this->assertStringStartsWith('$2y$', $hash);
        $this->assertTrue(Password::_verify('secret', $hash));
        $this->assertFalse(Password::_verify('wrong', $hash));
    }

    public function testVerifyRejectsEmptyStoredPassword(): void
    {
        $this->assertFalse(Password::_verify('anything', ''));
        $this->assertFalse(Password::_verify('anything', null));
    }

    public function testVerifyLegacySha1Hash(): void
    {
        $sha1 = sha1('legacy');
        $this->assertTrue(Password::_verify('legacy', $sha1));
        $this->assertFalse(Password::_verify('nope', $sha1));
    }

    public function testVerifyLegacyPlainText(): void
    {
        $this->assertTrue(Password::_verify('plain', 'plain'));
        $this->assertFalse(Password::_verify('plain', 'other'));
    }

    public function testNeedsRehash(): void
    {
        $this->assertFalse(Password::needsRehash(''));
        $this->assertTrue(Password::needsRehash(sha1('x')));
        $this->assertTrue(Password::needsRehash('plaintext'));
        $this->assertFalse(Password::needsRehash(Password::_crypt('x')));
    }

    public function testUpgradeStoredHashUpgradesLegacyOnCorrectPassword(): void
    {
        $legacy = sha1('mypass');
        $upgraded = Password::upgradeStoredHash('mypass', $legacy);
        $this->assertNotNull($upgraded);
        $this->assertStringStartsWith('$2y$', $upgraded);
        $this->assertTrue(Password::_verify('mypass', $upgraded));
    }

    public function testUpgradeStoredHashReturnsNullWhenWrongPassword(): void
    {
        $this->assertNull(Password::upgradeStoredHash('wrong', sha1('mypass')));
    }

    public function testUpgradeStoredHashReturnsNullWhenAlreadyModern(): void
    {
        $modern = Password::_crypt('mypass');
        $this->assertNull(Password::upgradeStoredHash('mypass', $modern));
    }

    public function testIsStoredHash(): void
    {
        $this->assertTrue(Password::isStoredHash(Password::_crypt('x')));
        $this->assertFalse(Password::isStoredHash('plaintext'));
        $this->assertFalse(Password::isStoredHash(sha1('x')));
    }

    public function testNetworkCleartextPrefersPppoePassword(): void
    {
        $customer = ['pppoe_password' => 'net-secret', 'password' => 'ignored'];
        $this->assertSame('net-secret', Password::networkCleartext($customer));
    }

    public function testNetworkCleartextFallsBackToLegacyPlainPassword(): void
    {
        $customer = ['pppoe_password' => '', 'password' => 'legacyplain'];
        $this->assertSame('legacyplain', Password::networkCleartext($customer));
    }

    public function testNetworkCleartextReturnsEmptyForHashedOnly(): void
    {
        $customer = ['pppoe_password' => '', 'password' => Password::_crypt('x')];
        $this->assertSame('', Password::networkCleartext($customer));

        $customer = ['pppoe_password' => '', 'password' => sha1('x')];
        $this->assertSame('', Password::networkCleartext($customer));
    }

    public function testNetworkCleartextHandlesNonArray(): void
    {
        $this->assertSame('', Password::networkCleartext('scalar'));
    }

    public function testAssignCustomerCredentialsSetsHashAndNetworkSecret(): void
    {
        $customer = new \stdClass();
        Password::assignCustomerCredentials($customer, 'portalpass', 'netpass');
        $this->assertTrue(Password::_verify('portalpass', $customer->password));
        $this->assertSame('netpass', $customer->pppoe_password);
    }

    public function testAssignCustomerCredentialsDefaultsNetworkToPlain(): void
    {
        $customer = new \stdClass();
        Password::assignCustomerCredentials($customer, 'portalpass');
        $this->assertSame('portalpass', $customer->pppoe_password);
    }

    public function testGenProducesEightCharacterPassword(): void
    {
        $this->assertSame(8, strlen(Password::_gen()));
    }
}
