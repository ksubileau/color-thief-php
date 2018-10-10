<?php

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
                    [87, 68, 79],
                    [210, 170, 127],
                    [158, 113, 84],
                    [157, 190, 175],
                    [107, 119, 129],
                    [52, 136, 211],
                    [29, 68, 84],
                    [120, 124, 101],
                    [212, 76, 60],
                ],
            ],
            [
                '/images/vegetables_1500x995.png',
                [
                    [45, 58, 23],
                    [227, 217, 199],
                    [96, 59, 49],
                    [117, 122, 46],
                    [107, 129, 102],
                    [176, 153, 102],
                    [191, 180, 144],
                    [159, 132, 146],
                    [60, 148, 44],
                ],
            ],
            [
                '/images/covers_cmyk_PR37.jpg',
                [
                    [223, 71, 106],
                    [21, 50, 129],
                    [143, 232, 249],
                    [238, 178, 163],
                    [163, 173, 59],
                    [94, 158, 245],
                    [99, 174, 248],
                    [120, 181, 169],
                    [68, 164, 168],
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
            [  0,   0,   0,      0, 0b000000000000000],
            [120, 120, 120,  15855, 0b011110111101111],
            [255, 255, 255,  32767, 0b111111111111111],
        ];
    }

    public function provide5bitsColorIndex_Bug()
    {
        return [
            [120, 120, 120, 126840, 0b011110111101111000],
            [255, 255, 255, 269535, 0b01000001110011011111],
        ];
    }

    public function provideNaturalOrderComparison()
    {
        return [
            [0, 5, -1],
            [10, -3, 1],
            [3, 3, 0],
        ];
    }

    /**
     * @dataProvider provideImageDominantColor
     *
     * @param string $image
     * @param array  $area
     * @param array  $expectedColor
     */
    public function testDominantColor($image, $area, $expectedColor)
    {
        $dominantColor = ColorThief::getColor(__DIR__ . $image, 10, $area);

        $this->assertSame($expectedColor, $dominantColor);
    }

    /**
     * @see Issue #13
     */
    public function testRemoteImage()
    {
        $dominantColor = ColorThief::getColor(
            'https://raw.githubusercontent.com/ksubileau/color-thief-php/master/tests/images/rails_600x406.gif'
        );
        $this->assertSame([88, 70, 80], $dominantColor);
    }

    /**
     * @dataProvider provideImageColorPalette
     *
     * @param string     $image
     * @param array      $expectedPalette
     * @param int        $quality
     * @param null|array $area
     */
    public function testPalette($image, $expectedPalette, $quality = 30, $area = null)
    {
        //$numColors = count($expectedPalette);
        $numColors = 10;
        $palette = ColorThief::getPalette(__DIR__ . $image, $numColors, $quality, $area);

        //$this->assertCount($numColors, $palette);
        $this->assertSame($expectedPalette, $palette);
    }

    /**
     * @dataProvider provideImageColorPalette
     *
     * @param string     $image
     * @param array      $expectedPalette
     * @param int        $quality
     * @param null|array $area
     */
    public function testPaletteBinaryString($image, $expectedPalette, $quality = 30, $area = null)
    {
        //$numColors = count($expectedPalette);
        $numColors = 10;
        $image = file_get_contents(__DIR__ . $image);
        $palette = ColorThief::getPalette($image, $numColors, $quality, $area);

        //$this->assertCount($numColors, $palette);
        $this->assertSame($expectedPalette, $palette);
    }

    public function testGetPaletteWithTooFewColors()
    {
        $this->setExpectedException('\InvalidArgumentException', 'The number of palette colors');

        ColorThief::getPalette('foo.jpg', 1);
    }

    public function testGetPaletteWithTooManyColors()
    {
        $this->setExpectedException('\InvalidArgumentException', 'The number of palette colors');

        ColorThief::getPalette('foo.jpg', 120000);
    }

    public function testGetPaletteWithInvalidQuality()
    {
        $this->setExpectedException('\InvalidArgumentException', 'quality argument');

        ColorThief::getPalette('foo.jpg', 5, 0);
    }

    /**
     * @see Issue #11
     */
    public function testGetPaletteWithBlankImage()
    {
        $this->setExpectedException('\RuntimeException', 'blank or transparent image', 1);

        ColorThief::getPalette(__DIR__ . '/images/blank.png');
    }

    /**
     * @dataProvider provide8bitsColorIndex
     *
     * @param int $r
     * @param int $g
     * @param int $b
     * @param int $index
     */
    public function testGetColorIndex8bits($r, $g, $b, $index)
    {
        $this->assertSame(
            $index,
            ColorThief::getColorIndex($r, $g, $b, 8)
        );
    }

    /**
     * @dataProvider provide5bitsColorIndex
     *
     * @param int $r
     * @param int $g
     * @param int $b
     * @param int $index
     * @param int $indexBinary
     */
    public function testGetColorIndex5bits($r, $g, $b, $index, $indexBinary)
    {
        $this->assertSame(
            $index,
            $indexBinary
        );
        $this->assertSame(
            $index,
            ColorThief::getColorIndex($r, $g, $b)
        );
    }

    /**
     * @dataProvider provide5bitsColorIndex_Bug
     *
     * @param int $r
     * @param int $g
     * @param int $b
     * @param int $index
     * @param int $indexBinary
     */
    public function testGetColorIndex5bits_Bug($r, $g, $b, $index, $indexBinary)
    {
        $this->assertSame(
            $index,
            $indexBinary
        );
        $this->assertNotEquals(
            $index,
            ColorThief::getColorIndex($r, $g, $b)
        );
    }

    /**
     * Tests RGB values are the same after converting them to a combined bucketInt and then back to RGB bucket values.
     *
     * @dataProvider provide5bitsColorIndex
     *
     * @param int $r
     * @param int $g
     * @param int $b
     * @param int $index
     * @param int $indexBinary
     *
     */
    public function testRgbValuesToBucketIntAndBackToBuckets($r, $g, $b, $index, $indexBinary)
    {
        $rgbBuckets = [$r >> ColorThief::RSHIFT, $g >> ColorThief::RSHIFT, $b >> ColorThief::RSHIFT];
        $this->assertSame(
            [$rgbBuckets[0], $rgbBuckets[1], $rgbBuckets[2]],
            ColorThief::getColorsFromIndex(ColorThief::getColorIndex($r, $g, $b, ColorThief::SIGBITS), 0, ColorThief::SIGBITS, 0)
        );

        // Test using getColorsFromIndex's default leftShift parameter
        $this->assertSame(
            [$rgbBuckets[0], $rgbBuckets[1], $rgbBuckets[2]],
            ColorThief::getColorsFromIndex(ColorThief::getColorIndex($r, $g, $b, ColorThief::SIGBITS), 0, ColorThief::SIGBITS)
        );
    }

    /**
     * Tests RGB values' significant bits are the same after converting them to a combined bucketInt and then back to RGB values.
     *
     * @dataProvider provide5bitsColorIndex
     *
     * @param int $r
     * @param int $g
     * @param int $b
     * @param int $index
     * @param int $indexBinary
     *
     */
    public function testRgbValuesToBucketIntAndBackToRgb($r, $g, $b, $index, $indexBinary)
    {
        $this->assertSame(
            [$r & 0b11111000, $g & 0b11111000, $b & 0b11111000],
            ColorThief::getColorsFromIndex(ColorThief::getColorIndex($r, $g, $b, ColorThief::SIGBITS), 0, ColorThief::SIGBITS, ColorThief::RSHIFT)
        );
    }

    /**
     * @dataProvider provide8bitsColorIndex
     *
     * @param int $r
     * @param int $g
     * @param int $b
     * @param int $index
     */
    public function testGetColorsFromIndex8bits($r, $g, $b, $index)
    {
        $this->assertSame(
            [$r, $g, $b],
            ColorThief::getColorsFromIndex($index, 0)
        );
    }

    /**
     * @dataProvider provideNaturalOrderComparison
     *
     * @param int $left
     * @param int $right
     * @param int $expected
     */
    public function testNaturalOrder($left, $right, $expected)
    {
        $this->assertSame(
            $expected,
            ColorThief::naturalOrder($left, $right)
        );
    }

    public function testGetHisto()
    {
        $method = new \ReflectionMethod('\ColorThief\ColorThief', 'getHisto');
        $method->setAccessible(true);

        // [[229, 210, 51], [133, 24, 135], [216, 235, 108], [132, 25, 134], [223, 46, 29],
        // [135, 28, 132], [233, 133, 213], [225, 212, 48]]
        $pixels = [15061555, 8722567, 14216044, 8657286, 14626333, 8854660, 15304149, 14799920];

        $expectedHisto = [
            29510 => 2,
            16496 => 3,
            28589 => 1,
            27811 => 1,
            30234 => 1,
        ];

        $this->assertSame($expectedHisto, $method->invoke(null, $pixels));
    }

    public function testVboxFromPixels()
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

    public function testDoCutLeftLetherThanRight()
    {
        $method = new \ReflectionMethod('\ColorThief\ColorThief', 'doCut');
        $method->setAccessible(true);

        // $left <= $right
        $result = $method->invoke(
            null,
            'g',
            new \ColorThief\VBox(0, 20, 0, 31, 0, 31, null),
            [38, 149, 556, 1222, 1830, 2656, 3638, 4744, 6039, 7412, 9039, 10686, 12244, 13715, 15091, 16355, 17599, 18768, 19771,
                20925, 22257, 24094, 25782, 27585, 28796, 29794, 30258, 30290, 30298, 30301, 30301, 30301, ],
            30301,
            [30263, 30152, 29745, 29079, 28471, 27645, 26663, 25557, 24262, 22889, 21262, 19615, 18057, 16586, 15210, 13946,
                12702, 11533, 10530, 9376, 8044, 6207, 4519, 2716, 1505, 507, 43, 11, 3, 0, 0, 0, ]
        );

        $this->assertEquals(new \ColorThief\VBox(0, 20, 0, 23, 0, 31, null), $result[0]);
        $this->assertEquals(new \ColorThief\VBox(0, 20, 24, 31, 0, 31, null), $result[1]);
    }

    public function testDoCutLeftGreaterThanRight()
    {
        $method = new \ReflectionMethod('\ColorThief\ColorThief', 'doCut');
        $method->setAccessible(true);

        // $left > $right
        $result = $method->invoke(
            null,
            'g',
            new \ColorThief\VBox(0, 13, 0, 17, 0, 10, null),
            [38, 149, 512, 1151, 1741, 2554, 3530, 4624, 5899, 7247, 8788, 10261, 11645, 12906, 13969, 14871, 15654, 16329],
            16329,
            [16291, 16180, 15817, 15178, 14588, 13775, 12799, 11705, 10430, 9082, 7541, 6068, 4684, 3423, 2360, 1458, 675, 0]
        );

        $this->assertEquals(new \ColorThief\VBox(0, 13, 0, 4, 0, 10, null), $result[0]);
        $this->assertEquals(new \ColorThief\VBox(0, 13, 5, 17, 0, 10, null), $result[1]);
    }
}
