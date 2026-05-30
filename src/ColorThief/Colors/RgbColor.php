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
 * Represents an sRGB color - the primary public-facing color class returned by ColorThief.
 */
readonly class RgbColor extends AbstractColor
{
    public function __construct(
        private int $red,
        private int $green,
        private int $blue,
        int $population = 0,
        float $proportion = 0.0,
    ) {
        parent::__construct($population, $proportion);
    }

    /** Red channel (0-255). */
    public function red(): int
    {
        return $this->red;
    }

    /** Green channel (0-255). */
    public function green(): int
    {
        return $this->green;
    }

    /** Blue channel (0-255). */
    public function blue(): int
    {
        return $this->blue;
    }

    public function toRgb(): self
    {
        return $this;
    }

    /** Returns CSS rgb() notation: "rgb(255, 128, 0)". */
    public function toCss(): string
    {
        return sprintf('rgb(%d, %d, %d)', $this->red, $this->green, $this->blue);
    }

    public function toString(): string
    {
        return $this->toCss();
    }

    public function toHex(string $prefix = ''): string
    {
        return sprintf('%s%02x%02x%02x', $prefix, $this->red, $this->green, $this->blue);
    }

    public function toInt(): int
    {
        return ($this->red << 16) + ($this->green << 8) + $this->blue;
    }

    /**
     * Components as [red, green, blue].
     *
     * @return array{int, int, int}
     */
    public function toArray(): array
    {
        return [$this->red, $this->green, $this->blue];
    }
}
