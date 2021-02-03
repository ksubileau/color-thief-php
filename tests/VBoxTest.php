<?php

declare(strict_types=1);

namespace ColorThief\Test;

use ColorThief\ColorThief;
use ColorThief\VBox;

class VBoxTest extends \PHPUnit\Framework\TestCase
{
    /** @var VBox */
    protected $vbox;

    protected function setUp(): void
    {
        $this->vbox = new VBox(0, 255, 0, 255, 0, 255, []);
    }

    protected function tearDown(): void
    {
        $this->vbox = null;
    }

    /**
     * @covers \ColorThief\VBox::volume
     */
    public function testVolume(): void
    {
        $this->vbox->r1 = 0;
        $this->vbox->r2 = 0;
        $this->vbox->g1 = 0;
        $this->vbox->g2 = 0;
        $this->vbox->b1 = 0;
        $this->vbox->b2 = 0;

        $this->assertSame(1, $this->vbox->volume());

        $this->vbox->r2 = 255;
        $this->vbox->g2 = 255;
        $this->vbox->b2 = 255;

        // Previous result should be cached.
        $this->assertSame(1, $this->vbox->volume());
        // Forcing refresh should now give the right result
        $this->assertSame(16777216, $this->vbox->volume(true));
    }

    /**
     * @covers \ColorThief\VBox::copy
     */
    public function testCopy(): void
    {
        $this->vbox->histo = [25 => 8];
        $copy = $this->vbox->copy();

        $this->assertInstanceOf('ColorThief\VBox', $copy);
        $this->assertSame($this->vbox->r1, $copy->r1);
        $this->assertSame($this->vbox->r2, $copy->r2);
        $this->assertSame($this->vbox->g1, $copy->g1);
        $this->assertSame($this->vbox->g2, $copy->g2);
        $this->assertSame($this->vbox->b1, $copy->b1);
        $this->assertSame($this->vbox->b2, $copy->b2);
        $this->assertSame($this->vbox->histo, $copy->histo);
    }

    /**
     * @covers \ColorThief\VBox::count
     */
    public function testCount(): void
    {
        $this->vbox->r1 = 225 >> ColorThief::RSHIFT;
        $this->vbox->r2 = 247 >> ColorThief::RSHIFT;
        $this->vbox->g1 = 180 >> ColorThief::RSHIFT;
        $this->vbox->g2 = 189 >> ColorThief::RSHIFT;
        $this->vbox->b1 = 130 >> ColorThief::RSHIFT;
        $this->vbox->b2 = 158 >> ColorThief::RSHIFT;

        //$pixels = array(0xE1BE9E, 0xC8BD9E, 0xFFBD9E, 0xE1329E, 0xE1C89E, 0xE1BD64, 0xE1BDC8);
        $this->vbox->histo = [
            29427 => 1,
            26355 => 1,
            32499 => 1,
            28883 => 1,
            29491 => 1,
            29420 => 1,
            29433 => 1,
        ];

        $this->assertEquals(1, $this->vbox->count());

        $this->vbox->histo[29427] = 2;
        $this->vbox->histo[30449] = 1;

        // Previous result should be cached.
        $this->assertEquals(1, $this->vbox->count());
        // Forcing refresh should now give the right result
        $this->assertEquals(3, $this->vbox->count(true));
    }

    /**
     * @covers \ColorThief\VBox::contains
     */
    public function testContains(): void
    {
        $this->vbox->r1 = 225 >> ColorThief::RSHIFT;
        $this->vbox->r2 = 247 >> ColorThief::RSHIFT;
        $this->vbox->g1 = 180 >> ColorThief::RSHIFT;
        $this->vbox->g2 = 189 >> ColorThief::RSHIFT;
        $this->vbox->b1 = 158 >> ColorThief::RSHIFT;
        $this->vbox->b2 = 158 >> ColorThief::RSHIFT;

        $this->assertTrue($this->vbox->contains([225, 190, 158]));

        $this->assertFalse($this->vbox->contains([200, 189, 158]));
        $this->assertFalse($this->vbox->contains([255, 189, 158]));

        $this->assertFalse($this->vbox->contains([225, 50, 158]));
        $this->assertFalse($this->vbox->contains([225, 200, 158]));

        $this->assertFalse($this->vbox->contains([225, 189, 100]));
        $this->assertFalse($this->vbox->contains([225, 189, 200]));
    }

    /**
     * @covers \ColorThief\VBox::longestAxis
     */
    public function testLongestAxis(): void
    {
        $this->vbox->r1 = 225 >> ColorThief::RSHIFT;
        $this->vbox->r2 = 247 >> ColorThief::RSHIFT;
        $this->vbox->g1 = 180 >> ColorThief::RSHIFT;
        $this->vbox->g2 = 189 >> ColorThief::RSHIFT;
        $this->vbox->b1 = 180 >> ColorThief::RSHIFT;
        $this->vbox->b2 = 228 >> ColorThief::RSHIFT;

        $this->assertEquals('b', $this->vbox->longestAxis());

        $this->vbox->g1 = 110 >> ColorThief::RSHIFT;
        $this->assertEquals('g', $this->vbox->longestAxis());

        $this->vbox->r1 = 10 >> ColorThief::RSHIFT;
        $this->assertEquals('r', $this->vbox->longestAxis());
    }

    /**
     * Test that avg() always returns values leather than 255.
     *
     * @see Issue #24
     */
    public function testAvgLimitAt255(): void
    {
        $this->vbox->r1 = 30;
        $this->vbox->r2 = 31;
        $this->vbox->g1 = 31;
        $this->vbox->g2 = 31;
        $this->vbox->b1 = 32;
        $this->vbox->b2 = 31;

        $this->assertSame([248, 252, 255], $this->vbox->avg());
    }
}
