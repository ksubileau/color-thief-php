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
use ColorThief\Internal\VBox;

class VBoxTest extends \PHPUnit\Framework\TestCase
{
    public function testVolume(): void
    {
        $vbox = new VBox(0, 255, 0, 255, 0, 255, [25 => 8]);
        $this->assertSame(16777216, $vbox->volume());

        $vbox->setAxisMax(Axis::X, 0);
        $vbox->setAxisMax(Axis::Y, 0);
        $vbox->setAxisMax(Axis::Z, 0);

        $this->assertSame(1, $vbox->volume());
    }

    public function testCopy(): void
    {
        $vbox = new VBox(0, 255, 0, 255, 0, 255, [25 => 8]);
        $copy = $vbox->copy();

        $this->assertEquals($vbox, $copy);
    }

    public function testCount(): void
    {
        $vbox = new VBox(
            225 >> 3,
            247 >> 3,
            180 >> 3,
            189 >> 3,
            130 >> 3,
            158 >> 3,
            [
                29427 => 2,
                26355 => 1,
                32499 => 1,
                28883 => 1,
                29491 => 1,
                29420 => 1,
                29433 => 1,
                30449 => 1,
            ]
        );

        $this->assertEquals(3, $vbox->count());
    }

    public function testContains(): void
    {
        $vbox = new VBox(225, 247, 180, 189, 158, 158, []);

        $this->assertTrue($vbox->contains(225, 189, 158));

        $this->assertFalse($vbox->contains(200, 189, 158));
        $this->assertFalse($vbox->contains(255, 189, 158));
        $this->assertFalse($vbox->contains(225, 50, 158));
        $this->assertFalse($vbox->contains(225, 200, 158));
        $this->assertFalse($vbox->contains(225, 189, 100));
        $this->assertFalse($vbox->contains(225, 189, 200));
    }

    public function testLongestAxis(): void
    {
        $vbox = new VBox(225, 247, 180, 189, 180, 228, []);
        $this->assertEquals(Axis::Z, $vbox->longestAxis());

        $vbox->setAxisMin(Axis::Y, 110);
        $this->assertEquals(Axis::Y, $vbox->longestAxis());

        $vbox->setAxisMin(Axis::X, 10);
        $this->assertEquals(Axis::X, $vbox->longestAxis());
    }

    /**
     * Test that avg() always returns values less than or equal to 255.
     *
     * @see Issue #24
     */
    public function testAvgLimitAt255(): void
    {
        $vbox = new VBox(30, 31, 31, 31, 32, 31, []);
        $this->assertSame([248, 252, 255], $vbox->avg());
    }
}
