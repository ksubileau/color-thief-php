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

namespace ColorThief\Colors;

use ColorThief\Internal\ColorMath;

/**
 * Base class for all color representations.
 */
abstract readonly class AbstractColor implements \Stringable
{
    use ColorMath;

    public function __construct(
        private int $population = 0,
        private float $proportion = 0.0,
    ) {
    }

    /** Number of pixels this color represents in the source image. */
    public function population(): int
    {
        return $this->population;
    }

    /** Fraction of the analysed pixels this color accounts for (0–1). */
    public function proportion(): float
    {
        return $this->proportion;
    }

    /** Convert to sRGB. */
    abstract public function toRgb(): RgbColor;

    /**
     * String representation of this color.
     *
     * CSS-capable colorspaces (RGB, HSL, OKLCH) delegate to toCss().
     * Non-CSS colorspaces (HSV, CMYK) return a descriptive functional notation.
     * The default falls back to the RGB hexadecimal notation.
     */
    public function toString(): string
    {
        return $this->toRgb()->toHex('#');
    }

    /**
     * Returns a packed integer representation of this color :
     * (red << 16 | green << 8 | blue)
     */
    public function toInt(): int
    {
        return $this->toRgb()->toInt();
    }

    /**
     * Hexadecimal sRGB representation, optionally prefixed (e.g. "#ff8000").
     * Non-RGB colorspaces round-trip through RGB.
     */
    public function toHex(string $prefix = ''): string
    {
        return $this->toRgb()->toHex($prefix);
    }

    /**
     * Returns a CSS functional notation for this color.
     */
    public function toCss(): string
    {
        return $this->toRgb()->toCss();
    }

    /**
     * Color components as a flat array in colorspace-native order.
     *
     * @return array<int|float>
     */
    abstract public function toArray(): array;

    /**
     * Relative luminance as defined by WCAG 2.x (0 = black, 1 = white).
     * Non-RGB colorspaces round-trip through RGB; for Color itself toRgb() returns $this.
     */
    final public function luminance(): float
    {
        $rgb = $this->toRgb();

        return static::rgbLuminance($rgb->red(), $rgb->green(), $rgb->blue());
    }

    /**
     * Returns true when the color is perceptually dark (luminance ≤ 0.179).
     */
    final public function isDark(): bool
    {
        return $this->luminance() <= 0.179;
    }

    /**
     * Returns true when the color is perceptually light.
     */
    final public function isLight(): bool
    {
        return !$this->isDark();
    }

    /**
     * Returns black or white — whichever provides the best contrast for text
     * rendered on top of this color.
     */
    final public function textColor(): RgbColor
    {
        return $this->isDark() ? new RgbColor(255, 255, 255) : new RgbColor(0, 0, 0);
    }

    final public function __toString(): string
    {
        return $this->toString();
    }
}
