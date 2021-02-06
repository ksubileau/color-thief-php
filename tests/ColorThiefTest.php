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

namespace ColorThief\Test;

use ColorThief\ColorThief;

class ColorThiefTest extends \PHPUnit\Framework\TestCase
{
    public function provideImageDominantColor()
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

    public function provideImageColorPalette()
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
                    [238, 178, 162],
                    [163, 173, 59],
                    [94, 158, 245],
                    [167, 39, 30],
                    [120, 181, 170],
                    [68, 168, 168],
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
        ];
    }

    public function provide8bitsColorIndex()
    {
        return [
            [0, 0, 0, 0],
            [120, 120, 120, 7895160],
            [255, 255, 255, 16777215],
        ];
    }

    public function provide5bitsColorIndex()
    {
        return [
            [0,     0,   0,  0b000000000000000],
            [120, 120, 120,  0b011110111101111],
            [255, 255, 255,  0b111111111111111],
        ];
    }

    /**
     * @dataProvider provideImageDominantColor
     */
    public function testDominantColor(string $image, ?array $area, array $expectedColor): void
    {
        $dominantColor = ColorThief::getColor(__DIR__.$image, 10, $area);

        $this->assertSame($expectedColor, $dominantColor);
    }

    /**
     * @see Issue #13
     */
    public function testRemoteImage(): void
    {
        $dominantColor = ColorThief::getColor(
            'https://raw.githubusercontent.com/ksubileau/color-thief-php/master/tests/images/rails_600x406.gif'
        );
        $this->assertSame([88, 70, 80], $dominantColor);
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

    /**
     * @dataProvider provideImageColorPalette
     */
    public function testPalette(string $image, array $expectedPalette, int $quality = 30, ?array $area = null): void
    {
        $numColors = \count($expectedPalette);
        $palette = ColorThief::getPalette(__DIR__.$image, $numColors, $quality, $area);

        $this->assertCount($numColors, $palette);
        $this->assertSame($expectedPalette, $palette);
    }

    /**
     * @dataProvider provideImageColorPalette
     */
    public function testPaletteBinaryString(string $image, array $expectedPalette, int $quality = 30, ?array $area = null): void
    {
        $numColors = \count($expectedPalette);
        $image = file_get_contents(__DIR__.$image);
        $palette = ColorThief::getPalette($image, $numColors, $quality, $area);

        $this->assertCount($numColors, $palette);
        $this->assertSame($expectedPalette, $palette);
    }

    public function testGetPaletteWithTooFewColors(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The number of palette colors');

        ColorThief::getPalette('foo.jpg', 1);
    }

    public function testGetPaletteWithTooManyColors(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The number of palette colors');

        ColorThief::getPalette('foo.jpg', 120000);
    }

    public function testGetPaletteWithInvalidQuality(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('quality argument');

        ColorThief::getPalette('foo.jpg', 5, 0);
    }

    /**
     * @see Issue #11
     */
    public function testGetPaletteWithBlankImage(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('blank or transparent image');
        $this->expectExceptionCode(1);

        ColorThief::getPalette(__DIR__.'/images/blank.png');
    }

    /**
     * @dataProvider provide8bitsColorIndex
     */
    public function testGetColorIndex8bits(int $r, int $g, int $b, int $index): void
    {
        $this->assertSame(
            $index,
            ColorThief::getColorIndex($r, $g, $b, 8)
        );
    }

    /**
     * @dataProvider provide5bitsColorIndex
     */
    public function testGetColorIndex5bits(int $r, int $g, int $b, int $index): void
    {
        $this->assertSame(
            $index,
            ColorThief::getColorIndex($r, $g, $b)
        );
    }

    /**
     * Tests RGB values are the same after converting them back from combined bucket index to RGB bucket values.
     *
     * @dataProvider provide5bitsColorIndex
     */
    public function testGetColorsFromIndex5bits(int $r, int $g, int $b, int $index): void
    {
        $rgbBuckets = [$r >> ColorThief::RSHIFT, $g >> ColorThief::RSHIFT, $b >> ColorThief::RSHIFT];
        $this->assertSame(
            [$rgbBuckets[0], $rgbBuckets[1], $rgbBuckets[2]],
            ColorThief::getColorsFromIndex($index, ColorThief::SIGBITS)
        );
    }

    /**
     * @dataProvider provide8bitsColorIndex
     */
    public function testGetColorsFromIndex8bits(int $r, int $g, int $b, int $index): void
    {
        $this->assertSame(
            [$r, $g, $b],
            ColorThief::getColorsFromIndex($index)
        );
    }

    public function testVboxFromPixels(): void
    {
        $method = new \ReflectionMethod('\ColorThief\ColorThief', 'vboxFromHistogram');
        $method->setAccessible(true);

        // [[229, 210, 51], [133, 24, 135], [216, 235, 108], [132, 25, 134], [223, 46, 29],
        // [135, 28, 132], [233, 133, 213], [225, 212, 48]]
        //$pixels = array(15061555, 8722567, 14216044, 8657286, 14626333, 8854660, 15304149, 14799920);

        $histo = [
            29510 => 2,
            16496 => 3,
            28589 => 1,
            27811 => 1,
            30234 => 1,
        ];

        $result = $method->invoke(null, $histo);

        $this->assertInstanceOf('\ColorThief\VBox', $result);
        $this->assertSame($histo, $result->histo);
        $this->assertSame(16, $result->r1);
        $this->assertSame(29, $result->r2);
        $this->assertSame(3, $result->g1);
        $this->assertSame(29, $result->g2);
        $this->assertSame(3, $result->b1);
        $this->assertSame(26, $result->b2);
    }

    /**
     * Tests min and max RGB values are equal if there is only one color in the histogram (PR #41).
     */
    public function testVboxFromSingleColorHistogram(): void
    {
        $method = new \ReflectionMethod('\ColorThief\ColorThief', 'vboxFromHistogram');
        $method->setAccessible(true);

        $histo = [
            26756 => 120000,
        ];

        $result = $method->invoke(null, $histo);

        $this->assertInstanceOf('\ColorThief\VBox', $result);
        $this->assertSame($histo, $result->histo);
        $this->assertSame($result->r1, $result->r2);
        $this->assertSame($result->g1, $result->g2);
        $this->assertSame($result->b1, $result->b2);
        $this->assertSame(1, $result->volume());
    }

    public function testDoCutLeftLeatherThanRight(): void
    {
        $method = new \ReflectionMethod('\ColorThief\ColorThief', 'doCut');
        $method->setAccessible(true);

        // $left <= $right
        $result = $method->invoke(
            null,
            'g',
            new \ColorThief\VBox(0, 20, 0, 31, 0, 31, []),
            [38, 149, 556, 1222, 1830, 2656, 3638, 4744, 6039, 7412, 9039, 10686, 12244, 13715, 15091, 16355, 17599, 18768, 19771,
                20925, 22257, 24094, 25782, 27585, 28796, 29794, 30258, 30290, 30298, 30301, 30301, 30301, ],
            30301,
            [30263, 30152, 29745, 29079, 28471, 27645, 26663, 25557, 24262, 22889, 21262, 19615, 18057, 16586, 15210, 13946,
                12702, 11533, 10530, 9376, 8044, 6207, 4519, 2716, 1505, 507, 43, 11, 3, 0, 0, 0, ]
        );

        $this->assertEquals(new \ColorThief\VBox(0, 20, 0, 23, 0, 31, []), $result[0]);
        $this->assertEquals(new \ColorThief\VBox(0, 20, 24, 31, 0, 31, []), $result[1]);
    }

    public function testDoCutLeftGreaterThanRight(): void
    {
        $method = new \ReflectionMethod('\ColorThief\ColorThief', 'doCut');
        $method->setAccessible(true);

        // $left > $right
        $result = $method->invoke(
            null,
            'g',
            new \ColorThief\VBox(0, 13, 0, 17, 0, 10, []),
            [38, 149, 512, 1151, 1741, 2554, 3530, 4624, 5899, 7247, 8788, 10261, 11645, 12906, 13969, 14871, 15654, 16329],
            16329,
            [16291, 16180, 15817, 15178, 14588, 13775, 12799, 11705, 10430, 9082, 7541, 6068, 4684, 3423, 2360, 1458, 675, 0]
        );

        $this->assertEquals(new \ColorThief\VBox(0, 13, 0, 4, 0, 10, []), $result[0]);
        $this->assertEquals(new \ColorThief\VBox(0, 13, 5, 17, 0, 10, []), $result[1]);
    }
}
