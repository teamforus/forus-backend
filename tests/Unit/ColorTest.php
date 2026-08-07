<?php

namespace Tests\Unit;

use App\Helpers\Color;
use InvalidArgumentException;
use Tests\TestCase;

class ColorTest extends TestCase
{
    /**
     * @return void
     */
    public function testToLuma(): void
    {
        $this->assertEqualsWithDelta(0.7152, (new Color(0, 255, 0))->toLuma(), 0.00001);
    }

    /**
     * @return void
     */
    public function testToHex(): void
    {
        $this->assertSame('#112233', (new Color(17, 34, 51))->toHex());
    }

    /**
     * @return void
     */
    public function testIsDark(): void
    {
        $this->assertTrue((new Color(17, 34, 51))->isDark());
        $this->assertFalse((new Color(255, 255, 255))->isDark());
    }

    /**
     * @return void
     */
    public function testNormalizeRgbHexAcceptsSupportedFormats(): void
    {
        $this->assertSame('#abc', Color::normalizeRgbHex('#abc'));
        $this->assertSame('#abc', Color::normalizeRgbHex('abc'));
        $this->assertSame('#aabbcc', Color::normalizeRgbHex('#aabbcc'));
        $this->assertSame('#aabbcc', Color::normalizeRgbHex('aabbcc'));
        $this->assertSame('#aabbcc', Color::normalizeRgbHex('#aabbccdd'));
        $this->assertSame('#aabbcc', Color::normalizeRgbHex('AABBCCDD'));
    }

    /**
     * @return void
     */
    public function testNormalizeRgbHexRejectsUnsupportedFormats(): void
    {
        $this->assertNull(Color::normalizeRgbHex('#abcd'));
        $this->assertNull(Color::normalizeRgbHex('#abcdd'));
        $this->assertNull(Color::normalizeRgbHex('#aabb'));
        $this->assertNull(Color::normalizeRgbHex('#aabbccddee'));
        $this->assertNull(Color::normalizeRgbHex('#aabbcc-nope'));
    }

    /**
     * @return void
     */
    public function testCreateFromHex(): void
    {
        $color = Color::createFromHex('#11223344');

        $this->assertSame(17, $color->red);
        $this->assertSame(34, $color->green);
        $this->assertSame(51, $color->blue);
    }

    /**
     * @return void
     */
    public function testCreateFromHexRejectsInvalidColor(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid hex color "#112233-nope".');

        Color::createFromHex('#112233-nope');
    }

    /**
     * @return void
     */
    public function testCreateRandom(): void
    {
        $color = Color::createRandom(10, 20);

        $this->assertGreaterThanOrEqual(10, $color->red);
        $this->assertLessThanOrEqual(235, $color->red);
        $this->assertGreaterThanOrEqual(10, $color->green);
        $this->assertLessThanOrEqual(235, $color->green);
        $this->assertGreaterThanOrEqual(10, $color->blue);
        $this->assertLessThanOrEqual(235, $color->blue);
    }

    /**
     * @return void
     */
    public function testRgb2Luma(): void
    {
        $this->assertEqualsWithDelta(0.2126, Color::rgb2luma(255, 0, 0), 0.00001);
    }

    /**
     * @return void
     */
    public function testRgb2Hex(): void
    {
        $this->assertSame('#112233', Color::rgb2hex(17, 34, 51));
    }

    /**
     * @return void
     */
    public function testHex2Rgb(): void
    {
        $this->assertSame([
            'red' => 170,
            'green' => 187,
            'blue' => 204,
        ], Color::hex2rgb('#abc'));
    }
}
