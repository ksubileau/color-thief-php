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

/**
 * Represents a color in the HSL colorspace.
 */
readonly class HslColor extends AbstractColor
{
    public function __construct(
        private float $hue,
        private float $saturation,
        private float $lightness,
        int $population = 0,
        float $proportion = 0.0,
    ) {
        parent::__construct($population, $proportion);
    }

    /** Hue angle in degrees (0–360). */
    public function hue(): float
    {
        return $this->hue;
    }

    /** Saturation (0–1). */
    public function saturation(): float
    {
        return $this->saturation;
    }

    /** Lightness (0–1). */
    public function lightness(): float
    {
        return $this->lightness;
    }

    public function toRgb(): RgbColor
    {
        [$r, $g, $b] = static::hslToRgb($this->hue, $this->saturation, $this->lightness);

        return new RgbColor($r, $g, $b, $this->population(), $this->proportion());
    }

    public function toHsl(): self
    {
        return $this;
    }

    /**
     * Returns CSS hsl() notation with integer values: "hsl(120, 50%, 70%)".
     */
    public function toCss(): string
    {
        return sprintf(
            'hsl(%d, %d%%, %d%%)',
            (int) round($this->hue),
            (int) round($this->saturation * 100),
            (int) round($this->lightness * 100),
        );
    }

    public function toString(): string
    {
        return $this->toCss();
    }

    /**
     * Components as [hue, saturation, lightness].
     *
     * @return array{float, float, float}
     */
    public function toArray(): array
    {
        return [$this->hue, $this->saturation, $this->lightness];
    }
}
