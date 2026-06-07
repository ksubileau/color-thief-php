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

namespace ColorThief\Image;

use ColorThief\ColorSpace;
use ColorThief\Internal\ColorMath;

/**
 * @internal
 */
readonly class PixelColor
{
    use ColorMath;

    public function __construct(
        public int $red,
        public int $green,
        public int $blue,
        public int $alpha = 0,
    ) {
    }

    /**
     * Convert this pixel color to the specified colorspace.
     *
     * @return array{int, int, int}
     */
    public function toColorspace(ColorSpace $colorSpace): array
    {
        return match ($colorSpace) {
            ColorSpace::Rgb => [$this->red, $this->green, $this->blue],
            ColorSpace::Oklch => (function () {
                [$l, $c, $h] = self::rgbToOklch($this->red, $this->green, $this->blue);

                return [
                    self::normalize($l, 1),
                    self::normalize($c, 0.4),
                    self::normalize($h, 360),
                ];
            })(),
        };
    }

    /**
     * Convert from the specified colorspace to RGB and return a new PixelColor.
     */
    public static function fromColorSpace(ColorSpace $colorSpace, int $x, int $y, int $z): self
    {
        $values = match ($colorSpace) {
            ColorSpace::Rgb => [$x, $y, $z],
            ColorSpace::Oklch => self::oklchToRgb(
                self::scale($x, 1),
                self::scale($y, 0.4),
                self::scale($z, 360),
            ),
        };

        return new self(...$values);
    }

    private static function scale(int $value, float $max): float
    {
        return max(min(($value / 255.0) * $max, $max), 0.0);
    }

    private static function normalize(float $value, float $max): int
    {
        return (int) max(min(round(($value / $max) * 255), 255), 0);
    }
}
