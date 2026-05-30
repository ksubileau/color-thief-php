<?php

/*
 * This file is part of the Color Thief PHP project.
 *
 * (c) Kevin Subileau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ColorThief\Internal;

/**
 * Pure color-math conversion functions shared by all color classes and PixelColor.
 *
 * All methods are protected static so they are accessible to any class that uses
 * this trait without being part of the public API.
 */
trait ColorMath
{
    /** Convert a single sRGB channel (0-255) to linear light (0-1). */
    protected static function srgbToLinear(int $c): float
    {
        $s = $c / 255;

        return $s <= 0.04045 ? $s / 12.92 : (($s + 0.055) / 1.055) ** 2.4;
    }

    /**
     * Relative luminance as defined by WCAG 2.x (0 = black, 1 = white).
     *
     * @param int $r Red channel (0–255)
     * @param int $g Green channel (0–255)
     * @param int $b Blue channel (0–255)
     */
    protected static function rgbLuminance(int $r, int $g, int $b): float
    {
        return 0.2126 * self::srgbToLinear($r)
            + 0.7152 * self::srgbToLinear($g)
            + 0.0722 * self::srgbToLinear($b);
    }
}
