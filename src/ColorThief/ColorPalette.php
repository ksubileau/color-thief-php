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

use ColorThief\Colors\AbstractColor;
use ColorThief\Colors\CmykColor;
use ColorThief\Colors\HslColor;
use ColorThief\Colors\HsvColor;
use ColorThief\Colors\OklchColor;
use ColorThief\Colors\RgbColor;

/**
 * Read-only ordered palette of colors.
 *
 * @template TColor of AbstractColor
 *
 * @implements \ArrayAccess<int, TColor>
 * @implements \IteratorAggregate<int, TColor>
 */
readonly class ColorPalette implements \ArrayAccess, \Countable, \IteratorAggregate
{
    /** @var list<TColor> */
    private array $colors;

    /**
     * Build an immutable palette of colors.
     *
     * @param TColor ...$colors Colors in palette order.
     */
    public function __construct(AbstractColor ...$colors)
    {
        $this->colors = array_values($colors);
    }

    /**
     * Number of colors in this palette.
     */
    public function count(): int
    {
        return \count($this->colors);
    }

    /**
     * Whether the palette has no colors.
     */
    public function isEmpty(): bool
    {
        return [] === $this->colors;
    }

    /**
     * Check if an index exists in the palette.
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->colors[$offset]);
    }

    /**
     * Get a color by zero-based index.
     *
     * @throws \OutOfBoundsException when index is invalid
     *
     * @return TColor
     */
    /** @return TColor */
    public function offsetGet(mixed $offset): AbstractColor
    {
        if (!$this->offsetExists($offset)) {
            throw new \OutOfBoundsException('Undefined palette index.');
        }

        return $this->colors[$offset];
    }

    /**
     * Palette is read-only.
     *
     * @throws \LogicException always
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException('ColorPalette is read-only.');
    }

    /**
     * Palette is read-only.
     *
     * @throws \LogicException always
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException('ColorPalette is read-only.');
    }

    /**
     * Iterate over palette colors in order.
     *
     * @return \Traversable<int, TColor>
     */
    public function getIterator(): \Traversable
    {
        /* @var \ArrayIterator<int, TColor> */
        return new \ArrayIterator($this->colors);
    }

    /**
     * @template T
     * Apply a transformation to each color and return mapped results.
     *
     * @param callable(TColor):T $callback
     *
     * @return list<T>
     */
    public function map(callable $callback): array
    {
        return array_map($callback, $this->colors);
    }

    /**
     * @template TCarry
     * Reduce palette colors to a single accumulated value.
     *
     * @param callable(TCarry, TColor):TCarry $callback
     * @param TCarry                          $initial
     *
     * @return TCarry
     */
    public function reduce(callable $callback, mixed $initial): mixed
    {
        return array_reduce($this->colors, $callback, $initial);
    }

    /**
     * Return a palette with every color converted to sRGB color space.
     *
     * @return ColorPalette<RgbColor>
     */
    public function toRgb(): self
    {
        return new self(...array_map(static fn (AbstractColor $color): RgbColor => $color->toRgb(), $this->colors));
    }

    /**
     * Return a palette with every color converted to Oklch color space.
     *
     * @return ColorPalette<OklchColor>
     */
    public function toOklch(): self
    {
        return new self(...array_map(static fn (AbstractColor $color): OklchColor => $color->toOklch(), $this->colors));
    }

    /**
     * Return a palette with every color converted to HSL color space.
     *
     * @return ColorPalette<HslColor>
     */
    public function toHsl(): self
    {
        return new self(...array_map(static fn (AbstractColor $color): HslColor => $color->toHsl(), $this->colors));
    }

    /**
     * Return a palette with every color converted to HSV color space.
     *
     * @return ColorPalette<HsvColor>
     */
    public function toHsv(): self
    {
        return new self(...array_map(static fn (AbstractColor $color): HsvColor => $color->toHsv(), $this->colors));
    }

    /**
     * Return a palette with every color converted to CMYK color space.
     *
     * @return ColorPalette<CmykColor>
     */
    public function toCmyk(): self
    {
        return new self(...array_map(static fn (AbstractColor $color): CmykColor => $color->toCmyk(), $this->colors));
    }

    /**
     * Export colors as component arrays in their current colorspace.
     *
     * @return list<array<int|float>>
     */
    public function toArray(): array
    {
        return array_map(static fn (AbstractColor $color): array => $color->toArray(), $this->colors);
    }

    /**
     * Export colors as an array of packed integers in sRGB space (RRGGBB).
     *
     * @return list<int>
     */
    public function toInt(): array
    {
        return array_map(static fn (AbstractColor $color): int => $color->toInt(), $this->colors);
    }

    /**
     * Export colors as an array of hexadecimal strings.
     *
     * @return list<string>
     */
    public function toHex(string $prefix = ''): array
    {
        return array_map(static fn (AbstractColor $color): string => $color->toHex($prefix), $this->colors);
    }

    /**
     * Export colors as an array of color object's string representations.
     *
     * @return list<string>
     */
    public function toString(): array
    {
        return array_map(static fn (AbstractColor $color): string => $color->toString(), $this->colors);
    }

    /**
     * Export colors as an array of CSS strings.
     *
     * @return list<string>
     */
    public function toCss(): array
    {
        return array_map(static fn (AbstractColor $color): string => $color->toCss(), $this->colors);
    }
}
