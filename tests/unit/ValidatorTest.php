<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Validator;

/**
 * @covers \Validator
 */
class ValidatorTest extends TestCase
{
    /**
     * @dataProvider emailProvider
     */
    public function testEmail(string $input, bool $expected): void
    {
        $this->assertSame($expected, Validator::Email($input));
    }

    public function emailProvider(): array
    {
        return [
            ['user@example.com', true],
            ['first.last@sub.example.co', true],
            ['not-an-email', false],
            ['missing@tld', false],
            ['@example.com', false],
        ];
    }

    public function testEmailRejectedWhenExcludedTextPresent(): void
    {
        $this->assertFalse(Validator::Email('spam@example.com', 'spam'));
        $this->assertTrue(Validator::Email('user@example.com', 'spam'));
    }

    /**
     * @dataProvider urlProvider
     */
    public function testUrl(string $input, bool $expected): void
    {
        $this->assertSame($expected, Validator::Url($input));
    }

    public function urlProvider(): array
    {
        return [
            ['http://example.com', true],
            ['https://example.com/path', true],
            ['ftp://files.example.com', true],
            ['example.com', false],
            ['justtext', false],
        ];
    }

    /**
     * @dataProvider ipProvider
     */
    public function testIp(string $input, bool $expected): void
    {
        $this->assertSame($expected, Validator::Ip($input));
    }

    public function ipProvider(): array
    {
        return [
            ['192.168.0.1', true],
            ['255.255.255.255', true],
            ['0.0.0.0', true],
            ['256.1.1.1', false],
            ['1.2.3', false],
            ['abc', false],
        ];
    }

    public function testUnsignedNumber(): void
    {
        $this->assertTrue(Validator::UnsignedNumber('123'));
        $this->assertTrue(Validator::UnsignedNumber('+7'));
        $this->assertFalse(Validator::UnsignedNumber('-7'));
        $this->assertFalse(Validator::UnsignedNumber('1.5'));
    }

    public function testNumberWithBounds(): void
    {
        $this->assertTrue(Validator::Number('5', 10, 0));
        $this->assertFalse(Validator::Number('10', 10, 0));
        $this->assertFalse(Validator::Number('0', 10, 0));
        $this->assertFalse(Validator::Number('abc'));
    }

    public function testAlphaAndAlphaNumeric(): void
    {
        $this->assertTrue(Validator::Alpha('Hello'));
        $this->assertFalse(Validator::Alpha('Hello1'));
        $this->assertTrue(Validator::AlphaNumeric('Hello1'));
        $this->assertFalse(Validator::AlphaNumeric('Hello 1'));
    }

    public function testCharsWithDefaultAndCustomRanges(): void
    {
        $this->assertTrue(Validator::Chars('abc'));
        $this->assertFalse(Validator::Chars('ABC'));
        $this->assertTrue(Validator::Chars('ABC', ['A-Z']));
    }

    public function testLengthBounds(): void
    {
        $this->assertTrue(Validator::Length('abcd', 10, 2));
        $this->assertFalse(Validator::Length('a', 10, 2));
        $this->assertFalse(Validator::Length('abcdefghijk', 10, 2));
    }

    /**
     * @dataProvider hexColorProvider
     */
    public function testHexColor(string $input, bool $expected): void
    {
        $this->assertSame($expected, Validator::HexColor($input));
    }

    public function hexColorProvider(): array
    {
        return [
            ['#ffffff', true],
            ['ffffff', true],
            ['#abc', true],
            ['#zzzzzz', false],
            ['#gg', false],
        ];
    }

    public function testDate(): void
    {
        $this->assertTrue(Validator::Date('2023-05-01'));
        $this->assertFalse(Validator::Date('not-a-date'));
    }

    /**
     * @dataProvider phoneProvider
     */
    public function testPhone(string $input, bool $expected): void
    {
        $this->assertSame($expected, Validator::Phone($input));
    }

    public function phoneProvider(): array
    {
        return [
            ['123-456-7890', true],
            ['(123) 456-789', true],
            ['1234567890', true],
            ['abc', false],
            ['12', false],
        ];
    }

    public function testCountRouterPlanAndHasPlan(): void
    {
        $plans = [
            ['routers' => 'r1'],
            ['routers' => 'r1'],
            ['routers' => 'r2'],
        ];
        $this->assertSame(2, Validator::countRouterPlan($plans, 'r1'));
        $this->assertSame(0, Validator::countRouterPlan($plans, 'r3'));
        $this->assertTrue(Validator::isRouterHasPlan($plans, 'r2'));
        $this->assertFalse(Validator::isRouterHasPlan($plans, 'r3'));
    }

    public function testContainsKeyword(): void
    {
        $this->assertSame(1, Validator::containsKeyword('MikroTik RB750'));
        $this->assertSame(1, Validator::containsKeyword('a hotspot device'));
        $this->assertSame(0, Validator::containsKeyword('generic switch'));
    }
}
