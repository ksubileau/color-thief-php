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

namespace ColorThief\Tests\Internal;

use ColorThief\Internal\Axis;
use ColorThief\Internal\Mmcq;
use ColorThief\Internal\VBox;
use PHPUnit\Framework\Attributes\DataProvider;

class MmcqTest extends \PHPUnit\Framework\TestCase
{
    public static function provide8bitsColorIndex(): array
    {
        return [
            [0, 0, 0, 0],
            [120, 120, 120, 7895160],
            [255, 255, 255, 16777215],
        ];
    }

    public static function provide5bitsColorIndex(): array
    {
        return [
            [0,     0,   0,  0b000000000000000],
            [120, 120, 120,  0b011110111101111],
            [255, 255, 255,  0b111111111111111],
        ];
    }

    #[DataProvider('provide8bitsColorIndex')]
    public function testGetColorIndex8bits(int $r, int $g, int $b, int $index): void
    {
        $this->assertSame(
            $index,
            Mmcq::getColorIndex($r, $g, $b, 8)
        );
    }

    #[DataProvider('provide5bitsColorIndex')]
    public function testGetColorIndex5bits(int $r, int $g, int $b, int $index): void
    {
        $this->assertSame(
            $index,
            Mmcq::getColorIndex($r, $g, $b)
        );
    }

    /**
     * Tests RGB values are the same after converting them back from combined bucket index to RGB bucket values.
     */
    #[DataProvider('provide5bitsColorIndex')]
    public function testGetColorsFromIndex5bits(int $r, int $g, int $b, int $index): void
    {
        $rgbBuckets = [$r >> Mmcq::RSHIFT, $g >> Mmcq::RSHIFT, $b >> Mmcq::RSHIFT];
        $this->assertSame(
            [$rgbBuckets[0], $rgbBuckets[1], $rgbBuckets[2]],
            Mmcq::getColorsFromIndex($index)
        );
    }

    #[DataProvider('provide8bitsColorIndex')]
    public function testGetColorsFromIndex8bits(int $r, int $g, int $b, int $index): void
    {
        $this->assertSame(
            [$r, $g, $b],
            Mmcq::getColorsFromIndex($index, 8)
        );
    }

    public function testVboxFromPixels(): void
    {
        // [[229, 210, 51], [133, 24, 135], [216, 235, 108], [132, 25, 134], [223, 46, 29],
        // [135, 28, 132], [233, 133, 213], [225, 212, 48]]
        // $pixels = array(15061555, 8722567, 14216044, 8657286, 14626333, 8854660, 15304149, 14799920);

        $histo = [
            29510 => 2,
            16496 => 3,
            28589 => 1,
            27811 => 1,
            30234 => 1,
        ];

        $result = Mmcq::vboxFromHistogram($histo);
        $this->assertEquals(new VBox(16, 29, 3, 29, 3, 26, $histo), $result);
    }

    /**
     * Tests min and max Vbox values are equal if there is only one color in the histogram (PR #41).
     */
    public function testVboxFromSingleColorHistogram(): void
    {
        $histo = [
            26756 => 120000,
        ];

        $result = Mmcq::vboxFromHistogram($histo);
        $this->assertEquals(new VBox(26, 26, 4, 4, 4, 4, $histo), $result);
        $this->assertSame(1, $result->volume());
    }

    public function testDoCutLeftLessThanRight(): void
    {
        // $left <= $right
        $result = Mmcq::doCut(
            Axis::Y,
            new VBox(0, 20, 0, 31, 0, 31, []),
            [38, 149, 556, 1222, 1830, 2656, 3638, 4744, 6039, 7412, 9039, 10686, 12244, 13715, 15091, 16355, 17599, 18768, 19771,
                20925, 22257, 24094, 25782, 27585, 28796, 29794, 30258, 30290, 30298, 30301, 30301, 30301, ],
            30301
        );

        $this->assertEquals(new VBox(0, 20, 0, 23, 0, 31, []), $result[0]);
        $this->assertEquals(new VBox(0, 20, 24, 31, 0, 31, []), $result[1]);
    }

    public function testDoCutLeftGreaterThanRight(): void
    {
        // $left > $right
        $result = Mmcq::doCut(
            Axis::Y,
            new VBox(0, 13, 0, 17, 0, 10, []),
            [38, 149, 512, 1151, 1741, 2554, 3530, 4624, 5899, 7247, 8788, 10261, 11645, 12906, 13969, 14871, 15654, 16329],
            16329
        );

        $this->assertEquals(new VBox(0, 13, 0, 4, 0, 10, []), $result[0]);
        $this->assertEquals(new VBox(0, 13, 5, 17, 0, 10, []), $result[1]);
    }
}
