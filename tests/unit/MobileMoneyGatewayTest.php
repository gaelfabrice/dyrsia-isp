<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use MobileMoneyGateway;

/**
 * @covers \MobileMoneyGateway
 */
class MobileMoneyGatewayTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        $GLOBALS['config'] = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        unset($GLOBALS['config']);
    }

    public function testActiveListParsesAndTrimsConfig(): void
    {
        $GLOBALS['config']['payment_gateway'] = ' campay , stripe ,, ';
        $this->assertSame(['campay', 'stripe'], MobileMoneyGateway::activeList());
    }

    public function testActiveListEmptyWhenUnset(): void
    {
        $this->assertSame([], MobileMoneyGateway::activeList());
    }

    public function testActiveMobilePrefersTenantOverride(): void
    {
        $GLOBALS['config']['tenant_mobile_gateway'] = 'mypvit';
        $GLOBALS['config']['payment_gateway'] = 'campay';
        $this->assertSame('mypvit', MobileMoneyGateway::activeMobile());
    }

    public function testActiveMobilePicksFirstMobileGateway(): void
    {
        $GLOBALS['config']['payment_gateway'] = 'stripe,campay';
        $this->assertSame('campay', MobileMoneyGateway::activeMobile());
    }

    public function testActiveMobileEmptyWhenNoMobileGateway(): void
    {
        $GLOBALS['config']['payment_gateway'] = 'stripe,paypal';
        $this->assertSame('', MobileMoneyGateway::activeMobile());
    }

    public function testNormalizeActivesKeepsSingleMobileGateway(): void
    {
        $result = MobileMoneyGateway::normalizeActives(['stripe', 'campay', 'mypvit']);
        // Only one mobile gateway may remain (the last one wins).
        $this->assertContains('mypvit', $result);
        $this->assertNotContains('campay', $result);
        $this->assertContains('stripe', $result);
    }

    public function testNormalizeActivesDeduplicatesAndTrims(): void
    {
        $result = MobileMoneyGateway::normalizeActives([' stripe ', 'stripe', '']);
        $this->assertSame(['stripe'], $result);
    }

    public function testIsConfiguredCampay(): void
    {
        $GLOBALS['config']['campay_username'] = 'u';
        $GLOBALS['config']['campay_password'] = 'p';
        $this->assertTrue(MobileMoneyGateway::isConfigured('campay'));

        $GLOBALS['config']['campay_password'] = '';
        $this->assertFalse(MobileMoneyGateway::isConfigured('campay'));
    }

    public function testIsConfiguredMypvitRequiresAllKeys(): void
    {
        $GLOBALS['config']['mypvit_code_url'] = 'x';
        $GLOBALS['config']['mypvit_secret_key'] = 'x';
        $GLOBALS['config']['mypvit_operation_account_code'] = 'x';
        $GLOBALS['config']['mypvit_callback_url_code'] = 'x';
        $this->assertTrue(MobileMoneyGateway::isConfigured('mypvit'));

        unset($GLOBALS['config']['mypvit_secret_key']);
        $this->assertFalse(MobileMoneyGateway::isConfigured('mypvit'));
    }

    public function testIsConfiguredUnknownGateway(): void
    {
        $this->assertFalse(MobileMoneyGateway::isConfigured('paypal'));
    }

    public function testHotspotPaymentProfileDefaultsToCameroonCampay(): void
    {
        $profile = MobileMoneyGateway::hotspotPaymentProfile('campay');
        $this->assertSame('campay', $profile['gateway']);
        $this->assertSame('237', $profile['prefix']);
        $this->assertSame('Cameroun', $profile['country']);
    }

    public function testHotspotPaymentProfileGabonForMypvit(): void
    {
        $GLOBALS['config']['mypvit_phone_prefix'] = '241';
        $profile = MobileMoneyGateway::hotspotPaymentProfile('mypvit');
        $this->assertSame('mypvit', $profile['gateway']);
        $this->assertSame('241', $profile['prefix']);
        $this->assertSame('Gabon', $profile['country']);
    }

    public function testHotspotPaymentProfileCameroonForMypvitWith237Prefix(): void
    {
        $GLOBALS['config']['mypvit_phone_prefix'] = '237';
        $profile = MobileMoneyGateway::hotspotPaymentProfile('mypvit');
        $this->assertSame('Cameroun', $profile['country']);
    }

    public function testOperatorInfoForPhoneDetectsMtnCameroon(): void
    {
        $info = MobileMoneyGateway::operatorInfoForPhone('677123456', 'campay');
        $this->assertSame('MTN', $info['operator']);
        $this->assertSame('*126#', $info['ussd']);
    }

    public function testOperatorInfoForPhoneDetectsOrangeCameroon(): void
    {
        $info = MobileMoneyGateway::operatorInfoForPhone('699123456', 'campay');
        $this->assertSame('Orange', $info['operator']);
    }

    public function testOperatorInfoForPhoneStripsPrefix(): void
    {
        $info = MobileMoneyGateway::operatorInfoForPhone('237677123456', 'campay');
        $this->assertSame('MTN', $info['operator']);
    }

    public function testOperatorInfoForPhoneFallsBackToGeneric(): void
    {
        $info = MobileMoneyGateway::operatorInfoForPhone('100000000', 'campay');
        $this->assertSame('Mobile Money', $info['operator']);
        $this->assertSame('', $info['ussd']);
    }

    public function testRememberAndTakeSubscriptionUssdRoundTrip(): void
    {
        MobileMoneyGateway::rememberSubscriptionUssd(42, 'MTN', '*126#');
        $taken = MobileMoneyGateway::takeSubscriptionUssd(42);
        $this->assertSame('MTN', $taken['operator']);
        $this->assertSame('*126#', $taken['ussd']);
        // Second read is cleared.
        $this->assertSame('', MobileMoneyGateway::takeSubscriptionUssd(42)['operator']);
    }

    public function testTakeSubscriptionUssdIgnoresMismatchedPaymentId(): void
    {
        MobileMoneyGateway::rememberSubscriptionUssd(1, 'MTN', '*126#');
        $taken = MobileMoneyGateway::takeSubscriptionUssd(999);
        $this->assertSame('', $taken['operator']);
        $this->assertSame('', $taken['ussd']);
    }

    public function testBuildHotspotPaymentJsBlockEmbedsProfile(): void
    {
        $js = MobileMoneyGateway::buildHotspotPaymentJsBlock('campay');
        $this->assertStringContainsString('HOTSPOT_PAYMENT_GATEWAY', $js);
        $this->assertStringContainsString('HOTSPOT_PAYMENT_PROFILE', $js);
        $this->assertStringContainsString('"campay"', $js);
    }
}
