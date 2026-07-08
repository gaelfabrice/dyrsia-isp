<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use MobileMoneyCountry;

/**
 * @covers \MobileMoneyCountry
 */
class MobileMoneyCountryTest extends TestCase
{
    protected function setUp(): void
    {
        // Pre-populate the translation table so Lang::T() returns the key
        // verbatim without touching config/language files on disk.
        $GLOBALS['_L'] = [
            'Please select your country' => 'Please select your country',
            'The selected country is not supported' => 'The selected country is not supported',
            'Payment gateway not configured for this country' => 'Payment gateway not configured for this country',
        ];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['_L']);
    }

    public function testCatalogContainsSupportedCountries(): void
    {
        $catalog = MobileMoneyCountry::catalog();
        $this->assertArrayHasKey('GA', $catalog);
        $this->assertArrayHasKey('CM', $catalog);
        $this->assertSame('mypvit', $catalog['GA']['gateway']);
        $this->assertSame('campay', $catalog['CM']['gateway']);
    }

    public function testResolveIsCaseInsensitiveAndTrims(): void
    {
        $this->assertSame('Gabon', MobileMoneyCountry::resolve('ga')['name']);
        $this->assertSame('Cameroun', MobileMoneyCountry::resolve('  cm ')['name']);
    }

    public function testResolveReturnsNullForUnknownCode(): void
    {
        $this->assertNull(MobileMoneyCountry::resolve('ZZ'));
    }

    public function testAvailableForProvisioningReturnsOnlyAvailable(): void
    {
        $available = MobileMoneyCountry::availableForProvisioning();
        $codes = array_column($available, 'code');
        $this->assertContains('GA', $codes);
        $this->assertContains('CM', $codes);
    }

    public function testDefaultCountryCode(): void
    {
        $this->assertSame('GA', MobileMoneyCountry::defaultCountryCode());
    }

    public function testValidateForProvisionSucceedsForSupportedCountry(): void
    {
        $result = MobileMoneyCountry::validateForProvision('ga');
        $this->assertTrue($result['ok']);
        $this->assertSame('Gabon', $result['country']['name']);
    }

    public function testValidateForProvisionFailsForEmptyCode(): void
    {
        $result = MobileMoneyCountry::validateForProvision('');
        $this->assertFalse($result['ok']);
        $this->assertSame('Please select your country', $result['message']);
    }

    public function testValidateForProvisionFailsForUnknownCode(): void
    {
        $result = MobileMoneyCountry::validateForProvision('ZZ');
        $this->assertFalse($result['ok']);
        $this->assertSame('The selected country is not supported', $result['message']);
    }
}
