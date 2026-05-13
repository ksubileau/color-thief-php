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

namespace ColorThief;

use ColorThief\Exception\NotSupportedException;

readonly class Color implements \Stringable
{
    public function __construct(
        private int $red = 0,
        private int $green = 0,
        private int $blue = 0,
        private int $population = 0,
        private float $proportion = 0,
    ) {
    }

    public function getRed(): int
    {
        return $this->red;
    }

    public function getGreen(): int
    {
        return $this->green;
    }

    public function getBlue(): int
    {
        return $this->blue;
    }

    public function getPopulation(): int
    {
        return $this->population;
    }

    public function getProportion(): float
    {
        return $this->proportion;
    }

    /**
     * Calculates integer value of current color instance.
     */
    public function getInt(): int
    {
        return ($this->red << 16) + ($this->green << 8) + $this->blue;
    }

    /**
     * Calculates hexadecimal value of current color instance.
     */
    public function getHex(string $prefix = ''): string
    {
        return sprintf('%s%02x%02x%02x', $prefix, $this->red, $this->green, $this->blue);
    }

    /**
     * Calculates RGB in array format of current color instance.
     *
     * @phpstan-return ColorRGB
     */
    public function getArray(): array
    {
        return [$this->red, $this->green, $this->blue];
    }

    /**
     * Calculates RGB in string format of current color instance.
     */
    public function getRgb(): string
    {
        return sprintf('rgb(%d, %d, %d)', $this->red, $this->green, $this->blue);
    }

    /**
     * Formats current color instance into given format.
     *
     * @phpstan-return ColorRGB|string|int|self
     */
    public function format(string $type): string|int|array|self
    {
        return match (strtolower($type)) {
            'rgb' => $this->getRgb(),
            'hex' => $this->getHex('#'),
            'int' => $this->getInt(),
            'array' => $this->getArray(),
            'obj' => $this,
            default => throw new NotSupportedException("Color format ({$type}) is not supported."),
        };
    }

    public function __toString(): string
    {
        return $this->getHex('#');
    }
}
