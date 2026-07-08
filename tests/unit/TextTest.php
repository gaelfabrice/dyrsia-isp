<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Text;

/**
 * @covers \Text
 */
class TextTest extends TestCase
{
    public function testAlphanumericStripsSpecialChars(): void
    {
        $this->assertSame('abc123', Text::alphanumeric('a b-c_1@2#3'));
    }

    public function testAlphanumericKeepsAllowedExtraChars(): void
    {
        $this->assertSame('a-b_c', Text::alphanumeric('a-b_c!', '-_'));
    }

    public function testNumericKeepsDigitsOnly(): void
    {
        $this->assertSame('12345', Text::numeric('+1 (234)-5 abc'));
        $this->assertSame('', Text::numeric('no-digits-here'));
    }

    public function testUcWordsReplacesUnderscoresAndCapitalises(): void
    {
        $this->assertSame('Hello World Foo', Text::ucWords('hello_world_foo'));
    }

    public function testSanitizeReplacesNonAlnumWithUnderscore(): void
    {
        $this->assertSame('a_b_c_', Text::sanitize('a b.c!'));
    }

    public function testIsHtmlDetectsTags(): void
    {
        $this->assertTrue(Text::is_html('<p>hi</p>'));
        $this->assertFalse(Text::is_html('plain text'));
    }

    /**
     * @dataProvider maskProvider
     */
    public function testMaskText(string $input, string $expected): void
    {
        $this->assertSame($expected, Text::maskText($input));
    }

    public function maskProvider(): array
    {
        return [
            'shorter than 3' => ['ab', '***'],
            'between 3 and 4' => ['abcd', 'a***d'],
            'between 5 and 7' => ['abcdef', 'ab***ef'],
            'eight or more'   => ['abcdefghij', 'abcd******hij'],
        ];
    }

    /**
     * @dataProvider dataUnitProvider
     */
    public function testConvertDataUnit(int $value, string $unit, int $expected): void
    {
        $this->assertSame($expected, Text::convertDataUnit($value, $unit));
    }

    public function dataUnitProvider(): array
    {
        return [
            'KB' => [2, 'KB', 2 * 1024],
            'MB' => [2, 'MB', 2 * 1048576],
            'GB' => [1, 'GB', 1073741824],
            'TB' => [1, 'TB', 1099511627776],
            'lowercase unit still matches' => [3, 'mb', 3 * 1048576],
            'unknown unit returns raw' => [5, 'PB', 5],
        ];
    }

    public function testJsonArray2textFlattensNestedArrays(): void
    {
        $result = Text::jsonArray2text(['a' => 1, 'b' => ['c' => 2]]);
        $this->assertSame("a = 1\nb.c = 2\n", $result);
    }

    public function testJsonArray21ArrayRebuildsFlatMap(): void
    {
        $result = Text::jsonArray21Array(['a' => 1, 'b' => ['c' => 2]]);
        $this->assertSame(['a' => '1', 'b.c' => '2'], $result);
    }

    public function testFixUrlConvertsFirstAmpersandToQuestionMark(): void
    {
        $this->assertSame('page?foo=1&bar=2', Text::fixUrl('page&foo=1&bar=2'));
    }

    public function testFixUrlLeavesUrlWithQueryUntouched(): void
    {
        $this->assertSame('page?foo=1', Text::fixUrl('page?foo=1'));
    }

    public function testRandomUpLowCasePreservesLettersCaseInsensitively(): void
    {
        $result = Text::randomUpLowCase('abcDEF');
        $this->assertSame('abcdef', strtolower($result));
        $this->assertSame(6, strlen($result));
    }
}
