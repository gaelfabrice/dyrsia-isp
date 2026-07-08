<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Timezone;

/**
 * @covers \Timezone
 */
class TimezoneTest extends TestCase
{
    public function testTimezoneListIsKeyedByIdentifierWithFormattedLabel(): void
    {
        $list = Timezone::timezoneList();
        $this->assertArrayHasKey('UTC', $list);
        $this->assertArrayHasKey('Africa/Libreville', $list);
        $this->assertStringContainsString('UTC', $list['UTC']);
        $this->assertStringEndsWith('UTC', $list['UTC']);
    }

    public function testGetTimeOffsetForUtcIsZero(): void
    {
        // Zero offset is emitted with a '-' sign by the implementation.
        $this->assertSame('-00:00', Timezone::getTimeOffset('UTC'));
    }

    public function testGetTimeOffsetForPositiveZone(): void
    {
        // Africa/Libreville is a fixed +01:00 zone (no DST).
        $this->assertSame('+01:00', Timezone::getTimeOffset('Africa/Libreville'));
    }
}
