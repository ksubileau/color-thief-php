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

namespace ColorThief\Tests;

use ColorThief\ColorPalette;
use ColorThief\Colors\AbstractColor;
use ColorThief\Colors\CmykColor;
use ColorThief\Colors\HslColor;
use ColorThief\Colors\HsvColor;
use ColorThief\Colors\OklchColor;
use ColorThief\Colors\RgbColor;
use PHPUnit\Framework\TestCase;

class ColorPaletteTest extends TestCase
{
    private function createPalette(): ColorPalette
    {
        return new ColorPalette(
            new RgbColor(209, 169, 127),
            new RgbColor(88, 68, 79),
            new RgbColor(158, 113, 84),
            new RgbColor(152, 188, 177),
            new RgbColor(106, 120, 129),
        );
    }

    public function testConstructorWithNamedArguments(): void
    {
        $red = new RgbColor(255, 0, 0);
        $blue = new RgbColor(0, 0, 255);

        $palette = new ColorPalette(primary: $red, secondary: $blue);

        $this->assertCount(2, $palette);
        $this->assertSame($red, $palette[0]);
        $this->assertSame($blue, $palette[1]);
        $this->assertFalse(isset($palette['primary']));
        $this->assertFalse(isset($palette['secondary']));
    }

    public function testIsEmpty(): void
    {
        $this->assertTrue((new ColorPalette())->isEmpty());
        $this->assertFalse((new ColorPalette(new RgbColor(10, 20, 30)))->isEmpty());
    }

    public function testArrayAccessAndCount(): void
    {
        $red = new RgbColor(255, 0, 0);
        $blue = new RgbColor(0, 0, 255);
        $palette = new ColorPalette($red, $blue);

        $this->assertCount(2, $palette);
        $this->assertSame($red, $palette[0]);
        $this->assertSame($blue, $palette[1]);
    }

    public function testReadOnlyPalette(): void
    {
        $palette = $this->createPalette();

        $this->expectException(\LogicException::class);
        $palette[0] = new RgbColor(1, 2, 3);
    }

    public function testMapMethodsReturnColorPalette(): void
    {
        $palette = $this->createPalette();

        $this->assertInstanceOf(ColorPalette::class, $palette->toRgb());
        $this->assertInstanceOf(RgbColor::class, $palette->toRgb()[0]);

        $this->assertInstanceOf(ColorPalette::class, $palette->toHsl());
        $this->assertInstanceOf(HslColor::class, $palette->toHsl()[0]);

        $this->assertInstanceOf(ColorPalette::class, $palette->toHsv());
        $this->assertInstanceOf(HsvColor::class, $palette->toHsv()[0]);

        $this->assertInstanceOf(ColorPalette::class, $palette->toCmyk());
        $this->assertInstanceOf(CmykColor::class, $palette->toCmyk()[0]);

        $this->assertInstanceOf(ColorPalette::class, $palette->toOklch());
        $this->assertInstanceOf(OklchColor::class, $palette->toOklch()[0]);
    }

    public function testMap(): void
    {
        $red = new RgbColor(255, 0, 0);
        $blue = new RgbColor(0, 0, 255);
        $palette = new ColorPalette($red, $blue);

        // Verify that map applies the callback to each color and collects results.
        $this->assertSame(
            [$red, $blue],
            $palette->map(static fn (AbstractColor $color): AbstractColor => $color)
        );
    }

    public function testReduce(): void
    {
        $red = new RgbColor(255, 0, 0);
        $blue = new RgbColor(0, 0, 255);
        $palette = new ColorPalette($red, $blue);

        // Verify that reduce accumulates the initial value across all colors.
        $this->assertSame(
            2,
            $palette->reduce(
                static fn (int $carry, AbstractColor $color): int => $carry + 1,
                0,
            )
        );

        // Verify that each color object is passed to the callback in order.
        $this->assertSame(
            [$red, $blue],
            $palette->reduce(
                static fn (array $carry, AbstractColor $color): array => [...$carry, $color],
                [],
            )
        );
    }

    public function testToArray(): void
    {
        $this->assertSame([
            [209, 169, 127],
            [88, 68, 79],
            [158, 113, 84],
            [152, 188, 177],
            [106, 120, 129],
        ], $this->createPalette()->toArray());
    }

    public function testToHex(): void
    {
        $this->assertSame([
            '#d1a97f',
            '#58444f',
            '#9e7154',
            '#98bcb1',
            '#6a7881',
        ], $this->createPalette()->toHex('#'));
    }

    public function testToInt(): void
    {
        $this->assertSame([
            13740415,
            5784655,
            10383700,
            10009777,
            6977665,
        ], $this->createPalette()->toInt());
    }

    public function testToString(): void
    {
        $this->assertSame([
            'rgb(209, 169, 127)',
            'rgb(88, 68, 79)',
            'rgb(158, 113, 84)',
            'rgb(152, 188, 177)',
            'rgb(106, 120, 129)',
        ], $this->createPalette()->toString());
    }

    public function testToCss(): void
    {
        $this->assertSame([
            'rgb(209, 169, 127)',
            'rgb(88, 68, 79)',
            'rgb(158, 113, 84)',
            'rgb(152, 188, 177)',
            'rgb(106, 120, 129)',
        ], $this->createPalette()->toCss());
    }
}
