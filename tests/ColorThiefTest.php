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
use ColorThief\Colors\RgbColor;
use ColorThief\ColorSwatches;
use ColorThief\ColorThief;
use ColorThief\Exception\InvalidArgumentException;
use ColorThief\Exception\NotSupportedException;
use ColorThief\Image\Adapter\AdapterInterface;
use PHPUnit\Framework\Attributes\DataProvider;

class ColorThiefTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Asserts that two palette colors are equal.
     */
    private function assertColorEquals(
        $expectedColor,
        AbstractColor $actualColor,
        string $messagePrefix = '',
        int $componentDelta = 1,
        int $populationDelta = 1,
        float $proportionDelta = 0.0002,
    ): void {
        $this->assertEqualsWithDelta($expectedColor->toArray(), $actualColor->toArray(), $componentDelta, "{$messagePrefix}components mismatch");
        $this->assertEqualsWithDelta($expectedColor->population(), $actualColor->population(), $populationDelta, "{$messagePrefix}population mismatch");
        $this->assertEqualsWithDelta($expectedColor->proportion(), $actualColor->proportion(), $proportionDelta, "{$messagePrefix}proportion mismatch");
    }

    /**
     * Asserts that two palettes are equal.
     */
    private function assertPaletteEquals(ColorPalette $expected, ColorPalette $actual): void
    {
        $this->assertEquals($expected->count(), $actual->count(), 'Palette color count mismatch');

        foreach ($expected as $index => $expectedColor) {
            $this->assertColorEquals($expectedColor, $actual[$index], "Color #{$index}: ");
        }
    }

    /**
     * Asserts that all expected swatches are present and RGB-close in actual swatches.
     */
    private function assertSwatchesMatch(ColorSwatches $expected, ColorSwatches $actual, int $componentDelta = 3): void
    {
        foreach (['vibrant', 'muted', 'darkVibrant', 'darkMuted', 'lightVibrant', 'lightMuted'] as $role) {
            /** @var ?RgbColor $expectedColor */
            $expectedColor = $expected->{$role};
            /** @var ?RgbColor $actualColor */
            $actualColor = $actual->{$role};

            if (null === $expectedColor) {
                $this->assertNull($actualColor, "Swatch '{$role}' should be null.");
                continue;
            }

            $this->assertNotNull($actualColor, "Swatch '{$role}' should not be null.");
            $this->assertColorEquals($expectedColor, $actualColor, "Swatch '{$role}': ");
        }
    }

    public static function provideImageDominantColor(): array
    {
        return [
            [
                '/images/rails_600x406.gif',
                null,
                new RgbColor(88, 70, 80, 14734, 0.60484),
            ],
            [
                '/images/field_1024x683.jpg',
                null,
                new RgbColor(107, 172, 222, 35527, 0.50796),
            ],
            [
                '/images/covers_cmyk_PR37.jpg',
                null,
                new RgbColor(135, 220, 248, 14292, 0.59546),
            ],
            [
                '/images/single_color_PR41.png',
                null,
                new RgbColor(181, 230, 29, 36000, 1.0),
            ],
            /*
            [  // WebP image
                '/images/donuts_PR45.webp',
                null,
                [204, 187, 177],
            ],
            */
            [  // Area targeting
                '/images/vegetables_1500x995.png',
                ['x' => 670, 'y' => 215, 'w' => 230, 'h' => 120],
                new RgbColor(63, 112, 24, 2404, 0.87101),
            ],
            [  // Area targeting with default values for y and width.
                '/images/vegetables_1500x995.png',
                ['x' => 1300, 'h' => 500],
                new RgbColor(54, 60, 33, 5608, 0.56080),
            ],
        ];
    }

    public static function provideImageColorPalette(): array
    {
        return [
            [
                '/images/rails_600x406.gif',
                new ColorPalette(
                    new RgbColor(210, 170, 127, 1561, 0.19224),
                    new RgbColor(88, 69, 81, 4559, 0.56145),
                    new RgbColor(158, 113, 84, 1696, 0.20887),
                    new RgbColor(157, 190, 175, 43, 0.00530),
                    new RgbColor(107, 119, 129, 115, 0.01416),
                    new RgbColor(82, 48, 33, 115, 0.01416),
                    new RgbColor(52, 136, 211, 11, 0.00135),
                    new RgbColor(29, 68, 84, 11, 0.00135),
                    new RgbColor(120, 124, 101, 6, 0.00074),
                    new RgbColor(212, 76, 60, 3, 0.00037),
                ),
            ],
            [
                '/images/vegetables_1500x995.png',
                new ColorPalette(
                    new RgbColor(227, 217, 199, 16069, 0.32346),
                    new RgbColor(96, 59, 49, 4597, 0.09253),
                    new RgbColor(45, 58, 23, 10394, 0.20922),
                    new RgbColor(117, 122, 46, 5329, 0.10727),
                    new RgbColor(107, 129, 102, 5044, 0.10153),
                    new RgbColor(176, 153, 102, 4443, 0.08943),
                    new RgbColor(191, 180, 144, 3494, 0.07033),
                    new RgbColor(159, 132, 146, 307, 0.00618),
                    new RgbColor(60, 148, 44, 1, 0.00002),
                    new RgbColor(68, 116, 124, 1, 0.00002),
                ),
            ],
            [
                '/images/covers_cmyk_PR37.jpg',
                new ColorPalette(
                    new RgbColor(141, 229, 249, 4140, 0.51750),
                    new RgbColor(21, 50, 129, 931, 0.11638),
                    new RgbColor(245, 84, 135, 624, 0.07809),
                    new RgbColor(238, 178, 163, 1049, 0.13113),
                    new RgbColor(163, 173, 59, 326, 0.04075),
                    new RgbColor(94, 158, 245, 671, 0.08388),
                    new RgbColor(167, 39, 30, 240, 0.02990),
                    new RgbColor(120, 181, 170, 17, 0.00213),
                    new RgbColor(68, 164, 168, 2, 0.00025),
                ),
            ],
            /*
            [
                '/images/donuts_PR45.webp',
                [
                    [44, 99, 88],
                    [212, 200, 182],
                    [148, 36, 46],
                    [185, 141, 29],
                    [183, 147, 89],
                    [175, 103, 144],
                    [118, 204, 192],
                ],
            ],
            */
        ];
    }

    public static function provideImageColorSwatches(): array
    {
        return [
            [
                '/images/rails_600x406.gif',
                new ColorSwatches(
                    vibrant: new RgbColor(212, 76, 60, 3, 0.00037),
                    muted: new RgbColor(158, 112, 82, 1641, 0.20209),
                    darkVibrant: null,
                    darkMuted: new RgbColor(82, 65, 80, 3770, 0.46429),
                    lightVibrant: null,
                    lightMuted: new RgbColor(157, 190, 175, 43, 0.00530),
                ),
            ],
            [
                '/images/vegetables_1500x995.png',
                new ColorSwatches(
                    vibrant: new RgbColor(204, 76, 148),
                    muted: new RgbColor(159, 132, 146, 307, 0.00618),
                    darkVibrant: null,
                    darkMuted: new RgbColor(42, 50, 22, 9223, 0.18565),
                    lightVibrant: null,
                    lightMuted: new RgbColor(231, 217, 198, 13734, 0.27645),
                ),
            ],
            [
                '/images/covers_cmyk_PR37.jpg',
                new ColorSwatches(
                    vibrant: new RgbColor(245, 83, 133, 565, 0.07068),
                    muted: new RgbColor(68, 164, 168, 2, 0.00025),
                    darkVibrant: null,
                    darkMuted: new RgbColor(20, 39, 82, 642, 0.0802),
                    lightVibrant: new RgbColor(252, 92, 156, 59, 0.00737),
                    lightMuted: new RgbColor(152, 250, 249, 2630, 0.32875),
                ),
            ],
            [
                '/images/single_color_PR41.png',
                new ColorSwatches(
                    vibrant: null,
                    muted: null,
                    darkVibrant: null,
                    darkMuted: null,
                    lightVibrant: new RgbColor(181, 230, 29, 12000, 1.0),
                    lightMuted: null,
                ),
            ],
        ];
    }

    #[DataProvider('provideImageDominantColor')]
    public function testDominantColor(string $image, ?array $area, RgbColor $expectedColor): void
    {
        $dominantColor = ColorThief::getColor(__DIR__.$image, 10, $area);

        $this->assertInstanceOf(RgbColor::class, $dominantColor);
        $this->assertColorEquals($expectedColor, $dominantColor);
    }

    /**
     * Asserts that the response palette includes the requested number of colors.
     */
    public function testPaletteColorCount(): void
    {
        $testWith = [2, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 20];
        foreach ($testWith as $numColors) {
            $image = '/images/vegetables_1500x995.png';
            $palette = ColorThief::getPalette(__DIR__.$image, $numColors, 200);

            $this->assertCount($numColors, $palette);
        }
    }

    #[DataProvider('provideImageColorPalette')]
    public function testPalette(string $image, ColorPalette $expectedPalette, int $quality = 30, ?array $area = null): void
    {
        $numColors = \count($expectedPalette);
        $palette = ColorThief::getPalette(__DIR__.$image, $numColors, $quality, $area);

        $this->assertCount($numColors, $palette);
        $this->assertPaletteEquals($expectedPalette, $palette);
    }

    #[DataProvider('provideImageColorPalette')]
    public function testPaletteBinaryString(string $image, ColorPalette $expectedPalette, int $quality = 30, ?array $area = null): void
    {
        $numColors = \count($expectedPalette);
        $image = file_get_contents(__DIR__.$image);
        $palette = ColorThief::getPalette($image, $numColors, $quality, $area);

        $this->assertCount($numColors, $palette);
        $this->assertPaletteEquals($expectedPalette, $palette);
    }

    #[DataProvider('provideImageColorSwatches')]
    public function testSwatches(string $image, ColorSwatches $expectedSwatches, int $quality = 30, ?array $area = null): void
    {
        $swatches = ColorThief::getSwatches(__DIR__.$image, $quality, $area);
        $this->assertSwatchesMatch($expectedSwatches, $swatches);
    }

    public function testGetPaletteWithCustomAdapter(): void
    {
        $adapter = $this->createMock(AdapterInterface::class);

        // Stub adapter that simulates a monochrome image
        $adapter->method('loadFromPath')->willReturnSelf();
        $adapter->method('getWidth')->willReturn(2);
        $adapter->method('getHeight')->willReturn(2);
        $adapter->method('getPixelColor')->willReturn(
            new \ColorThief\Image\PixelColor(red: 24, green: 60, blue: 100, alpha: 0),
            new \ColorThief\Image\PixelColor(red: 24, green: 60, blue: 100, alpha: 0),
            new \ColorThief\Image\PixelColor(red: 56, green: 20, blue: 14, alpha: 0),
            new \ColorThief\Image\PixelColor(red: 56, green: 20, blue: 14, alpha: 0)
        );

        $palette = ColorThief::getPalette(__DIR__.'/images/rails_600x406.gif', 2, 1, null, $adapter);

        $this->assertPaletteEquals(
            new ColorPalette(
                new RgbColor(24, 60, 100, 2, 0.5),
                new RgbColor(56, 20, 14, 2, 0.5),
            ),
            $palette
        );
    }

    public function testGetPaletteWithTooFewColors(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The number of palette colors');

        ColorThief::getPalette('foo.jpg', 1);
    }

    public function testGetPaletteWithTooManyColors(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The number of palette colors');

        ColorThief::getPalette('foo.jpg', 21);
    }

    public function testGetPaletteWithInvalidQuality(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('quality argument');

        ColorThief::getPalette('foo.jpg', 5, 0);
    }

    public function testGetPaletteWithSingleColorImage(): void
    {
        $palette = ColorThief::getPalette(__DIR__.'/images/single_color_PR41.png');
        $this->assertPaletteEquals(
            new ColorPalette(new RgbColor(181, 230, 29, 36000, 1)),
            $palette
        );
    }

    /**
     * @see Issue #11
     */
    public function testGetPaletteWithBlankImage(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('blank or transparent image');

        ColorThief::getPalette(__DIR__.'/images/blank.png');
    }
}
