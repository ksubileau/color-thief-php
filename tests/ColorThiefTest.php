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

use ColorThief\Color;
use ColorThief\ColorThief;
use ColorThief\Exception\InvalidArgumentException;
use ColorThief\Exception\NotSupportedException;
use ColorThief\Image\Adapter\AdapterInterface;
use PHPUnit\Framework\Attributes\DataProvider;

class ColorThiefTest extends \PHPUnit\Framework\TestCase
{
    public static function provideImageDominantColor(): array
    {
        return [
            [
                '/images/rails_600x406.gif',
                null,
                [88, 70, 80],
            ],
            [
                '/images/field_1024x683.jpg',
                null,
                [107, 172, 222],
            ],
            [
                '/images/covers_cmyk_PR37.jpg',
                null,
                [135, 220, 248],
            ],
            [
                '/images/single_color_PR41.png',
                null,
                [180, 228, 28],
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
                [63, 112, 24],
            ],
            [  // Area targeting with default values for y and width.
                '/images/vegetables_1500x995.png',
                ['x' => 1300, 'h' => 500],
                [54, 60, 33],
            ],
        ];
    }

    public static function provideImageColorPalette(): array
    {
        return [
            [
                '/images/rails_600x406.gif',
                [
                    [210, 170, 127],
                    [88, 69, 81],
                    [158, 113, 84],
                    [157, 190, 175],
                    [107, 119, 129],
                    [82, 48, 33],
                    [52, 136, 211],
                    [29, 68, 84],
                    [120, 124, 101],
                    [212, 76, 60],
                ],
            ],
            [
                '/images/vegetables_1500x995.png',
                [
                    [227, 217, 199],
                    [96, 59, 49],
                    [45, 58, 23],
                    [117, 122, 46],
                    [107, 129, 102],
                    [176, 153, 102],
                    [191, 180, 144],
                    [159, 132, 146],
                    [60, 148, 44],
                    [68, 116, 124],
                ],
            ],
            [
                '/images/covers_cmyk_PR37.jpg',
                [
                    [141, 229, 249],
                    [21, 50, 129],
                    [245, 84, 135],
                    [238, 178, 163],
                    [163, 173, 59],
                    [94, 158, 245],
                    [167, 39, 30],
                    [120, 181, 170],
                    [68, 164, 168],
                ],
            ],
            [
                '/images/single_color_PR41.png',
                [
                    [180, 228, 28],
                    [184, 228, 28],
                    [184, 228, 28],
                    [184, 228, 28],
                    [184, 228, 28],
                ],
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

    #[DataProvider('provideImageDominantColor')]
    public function testDominantColor(string $image, ?array $area, array $expectedColor): void
    {
        $dominantColor = ColorThief::getColor(__DIR__.$image, 10, $area);

        $this->assertSame($expectedColor, $dominantColor);
    }

    public function testDominantColorFormat(): void
    {
        $dominantColor = ColorThief::getColor(__DIR__.'/images/rails_600x406.gif', 10, null, 'array');
        $this->assertSame([88, 70, 80], $dominantColor);

        $dominantColor = ColorThief::getColor(__DIR__.'/images/rails_600x406.gif', 10, null, 'hex');
        $this->assertSame('#584650', $dominantColor);

        $dominantColor = ColorThief::getColor(__DIR__.'/images/rails_600x406.gif', 10, null, 'int');
        $this->assertSame(5785168, $dominantColor);

        $dominantColor = ColorThief::getColor(__DIR__.'/images/rails_600x406.gif', 10, null, 'rgb');
        $this->assertSame('rgb(88, 70, 80)', $dominantColor);

        $dominantColor = ColorThief::getColor(__DIR__.'/images/rails_600x406.gif', 10, null, 'obj');
        $this->assertInstanceOf(Color::class, $dominantColor);
        $this->assertEquals(88, $dominantColor->getRed());
        $this->assertEquals(70, $dominantColor->getGreen());
        $this->assertEquals(80, $dominantColor->getBlue());
        $this->assertEquals(14734, $dominantColor->getPopulation());
        $this->assertEqualsWithDelta(0.6048, $dominantColor->getProportion(), 1e-4);
    }

    /**
     * Asserts that the response palette includes the requested number of colors.
     */
    public function testPaletteColorCount(): void
    {
        $testWith = [4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 32, 64, 128, 256];
        foreach ($testWith as $numColors) {
            $image = '/images/single_color_PR41.png';
            $palette = ColorThief::getPalette(__DIR__.$image, $numColors, 30);

            $this->assertCount($numColors, $palette);
        }
    }

    #[DataProvider('provideImageColorPalette')]
    public function testPalette(string $image, array $expectedPalette, int $quality = 30, ?array $area = null): void
    {
        $numColors = \count($expectedPalette);
        $palette = ColorThief::getPalette(__DIR__.$image, $numColors, $quality, $area);

        $this->assertCount($numColors, $palette);
        $this->assertSame($expectedPalette, $palette);
    }

    public function testPaletteFormat(): void
    {
        $dominantColor = ColorThief::getPalette(__DIR__.'/images/rails_600x406.gif', 8, 10, null, 'array');
        $this->assertSame([
            [209, 169, 127],
            [88, 68, 79],
            [158, 113, 84],
            [152, 188, 177],
            [106, 120, 129],
            [96, 144, 196],
            [118, 124, 101],
            [28, 68, 81],
        ], $dominantColor);

        $dominantColor = ColorThief::getPalette(__DIR__.'/images/rails_600x406.gif', 8, 10, null, 'hex');
        $this->assertSame([
            '#d1a97f',
            '#58444f',
            '#9e7154',
            '#98bcb1',
            '#6a7881',
            '#6090c4',
            '#767c65',
            '#1c4451',
        ], $dominantColor);

        $dominantColor = ColorThief::getPalette(__DIR__.'/images/rails_600x406.gif', 8, 10, null, 'int');
        $this->assertSame([
            13740415,
            5784655,
            10383700,
            10009777,
            6977665,
            6328516,
            7765093,
            1852497,
        ], $dominantColor);

        $dominantColor = ColorThief::getPalette(__DIR__.'/images/rails_600x406.gif', 8, 10, null, 'rgb');
        $this->assertSame([
            'rgb(209, 169, 127)',
            'rgb(88, 68, 79)',
            'rgb(158, 113, 84)',
            'rgb(152, 188, 177)',
            'rgb(106, 120, 129)',
            'rgb(96, 144, 196)',
            'rgb(118, 124, 101)',
            'rgb(28, 68, 81)',
        ], $dominantColor);
    }

    #[DataProvider('provideImageColorPalette')]
    public function testPaletteBinaryString(string $image, array $expectedPalette, int $quality = 30, ?array $area = null): void
    {
        $numColors = \count($expectedPalette);
        $image = file_get_contents(__DIR__.$image);
        $palette = ColorThief::getPalette($image, $numColors, $quality, $area);

        $this->assertCount($numColors, $palette);
        $this->assertSame($expectedPalette, $palette);
    }

    public function testGetPaletteWithCustomAdapter(): void
    {
        $adapter = $this->createMock(AdapterInterface::class);

        // Stub adapter that simulates a monochrome image
        $adapter->method('loadFromPath')->willReturnSelf();
        $adapter->method('getWidth')->willReturn(500);
        $adapter->method('getHeight')->willReturn(500);
        $adapter->method('getPixelColor')->willReturn(new \ColorThief\Image\PixelColor(red: 24, green: 60, blue: 100, alpha: 0));

        $palette = ColorThief::getPalette(__DIR__.'/images/rails_600x406.gif', 5, 10, null, 'array', $adapter);

        $this->assertSame([
            [28, 60, 100],
            [32, 60, 100],
            [32, 60, 100],
            [32, 60, 100],
            [32, 60, 100],
        ], $palette);
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

        ColorThief::getPalette('foo.jpg', 120000);
    }

    public function testGetPaletteWithInvalidQuality(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('quality argument');

        ColorThief::getPalette('foo.jpg', 5, 0);
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
