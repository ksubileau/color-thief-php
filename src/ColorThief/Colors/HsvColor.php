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
 * Represents a color in the HSV colorspace.
 */
readonly class HsvColor extends AbstractColor
{
    public function __construct(
        private float $hue,
        private float $saturation,
        private float $value,
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

    /** Value / brightness (0–1). */
    public function value(): float
    {
        return $this->value;
    }

    public function toRgb(): RgbColor
    {
        [$r, $g, $b] = static::hsvToRgb($this->hue, $this->saturation, $this->value);

        return new RgbColor($r, $g, $b, $this->population(), $this->proportion());
    }

    public function toHsv(): self
    {
        return $this;
    }

    public function toString(): string
    {
        return sprintf(
            'hsv(%d, %d%%, %d%%)',
            (int) round($this->hue),
            (int) round($this->saturation * 100),
            (int) round($this->value * 100),
        );
    }

    /**
     * Components as [hue, saturation, value].
     *
     * @return array{float, float, float}
     */
    public function toArray(): array
    {
        return [$this->hue, $this->saturation, $this->value];
    }
}
