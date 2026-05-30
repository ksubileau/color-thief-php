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
 * Represents a color in the OKLCH colorspace.
 */
readonly class OklchColor extends AbstractColor
{
    public function __construct(
        private float $lightness,
        private float $chroma,
        private float $hue,
        int $population = 0,
        float $proportion = 0.0,
    ) {
        parent::__construct($population, $proportion);
    }

    /** Lightness (0–1). */
    public function lightness(): float
    {
        return $this->lightness;
    }

    /** Chroma / colorfulness (0–~0.4). */
    public function chroma(): float
    {
        return $this->chroma;
    }

    /** Hue angle in degrees (0–360). */
    public function hue(): float
    {
        return $this->hue;
    }

    public function toRgb(): RgbColor
    {
        [$r, $g, $b] = static::oklchToRgb($this->lightness, $this->chroma, $this->hue);

        return new RgbColor($r, $g, $b, $this->population(), $this->proportion());
    }

    public function toOklch(): self
    {
        return $this;
    }

    /**
     * Returns CSS oklch() notation: "oklch(0.7098 0.1572 326.23)".
     */
    public function toCss(): string
    {
        return sprintf('oklch(%.4f %.4f %.2f)', $this->lightness, $this->chroma, $this->hue);
    }

    public function toString(): string
    {
        return $this->toCss();
    }

    /**
     * Components as [lightness, chroma, hue].
     *
     * @return array{float, float, float}
     */
    public function toArray(): array
    {
        return [$this->lightness, $this->chroma, $this->hue];
    }
}
