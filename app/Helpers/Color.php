<?php


namespace App\Helpers;


/**
 * Class Color
 * @package App\Helpers
 */
class Color
{
    protected const LUMA_THRESHOLD = .75;

    public $red;
    public $green;
    public $blue;

    public function __construct(int $red = 0, int $green = 0, int $blue = 0)
    {
        $this->red = $red;
        $this->green = $green;
        $this->blue = $blue;
    }

    /**
     * @return float|int
     */
    public function toLuma()
    {
        return self::rgb2luma($this->red, $this->green, $this->blue);
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function toHex(): string
    {
        return self::rgb2hex($this->red, $this->green, $this->blue);
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function isDark(): string
    {
        return $this->toLuma() < self::LUMA_THRESHOLD;
    }

    /**
     * @param string $hex
     * @return Color
     * @noinspection PhpUnused
     */
    public static function createFromHex(string $hex): Color
    {
        $color = self::hex2rgb($hex);

        return new self($color['red'], $color['green'], $color['blue']);
    }

    /**
     * @param int $paddingLight
     * @param int|null $paddingDark
     * @return Color
     */
    public static function createRandom(int $paddingLight = 0, int $paddingDark = null): Color
    {
        if (is_null($paddingDark)) {
            $paddingDark = $paddingLight;
        }

        return new self(
            rand(0 + $paddingLight, 255 - $paddingDark),
            rand(0 + $paddingLight, 255 - $paddingDark),
            rand(0 + $paddingLight, 255 - $paddingDark)
        );
    }

    /**
     * @param int $red
     * @param int $green
     * @param int $blue
     * @return float|int
     */
    public static function rgb2luma(int $red, int $green, int $blue): float
    {
        return (0.2126 * $red + 0.7152 * $green + 0.0722 * $blue) / 255;
    }

    /**
     * @param int $red
     * @param int $green
     * @param int $blue
     * @return string
     */
    public static function rgb2hex(int $red, int $green, int $blue): string
    {
        return sprintf("#%02x%02x%02x", $red, $green, $blue);
    }

    /**
     * Convert a hexadecimal color code to its RGB equivalent
     *
     * @param string $hexStr (hexadecimal color value)
     * @param boolean $returnAsString (if set true, returns the value separated by the separator character. Otherwise, returns associative array)
     * @param string $separator (to separate RGB values. Applicable only if second parameter is true.)
     * @return array|string (depending on second parameter. Returns False if invalid hex color value)
     */
    public static function hex2rgb($hexStr, $returnAsString = false, $separator = ',')
    {
        // Gets a proper hex string
        $hexStr = preg_replace("/[^0-9A-Fa-f]/", '', $hexStr);
        $rgbArray = [];

        // If a proper hex code, convert using bitwise operation. No overhead... faster
        if (strlen($hexStr) == 6) {
            $colorVal = hexdec($hexStr);
            $rgbArray['red'] = 0xFF & ($colorVal >> 0x10);
            $rgbArray['green'] = 0xFF & ($colorVal >> 0x8);
            $rgbArray['blue'] = 0xFF & $colorVal;
        } elseif (strlen($hexStr) == 3) { //if shorthand notation, need some string manipulations
            $rgbArray['red'] = hexdec(str_repeat(substr($hexStr, 0, 1), 2));
            $rgbArray['green'] = hexdec(str_repeat(substr($hexStr, 1, 1), 2));
            $rgbArray['blue'] = hexdec(str_repeat(substr($hexStr, 2, 1), 2));
        } else {
            // Invalid hex color code
            return null;
        }

        // returns the rgb string or the associative array
        return $returnAsString ? implode($separator, $rgbArray) : $rgbArray;
    }
}
