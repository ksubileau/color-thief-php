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

namespace ColorThief\Tests\Colors;

use ColorThief\Colors\RgbColor;

class RgbColorTest extends AbstractColorTest
{
    public function testConstructorAndAccessors(): void
    {
        $c = new RgbColor(181, 55, 23, 1000, 0.42);
        $this->assertSame(181, $c->red());
        $this->assertSame(55, $c->green());
        $this->assertSame(23, $c->blue());
        $this->assertSame(1000, $c->population());
        $this->assertSame(0.42, $c->proportion());
    }

    public function testToCss(): void
    {
        $this->assertSame('rgb(0, 0, 0)', (new RgbColor(0, 0, 0))->toCss());
        $this->assertSame('rgb(255, 255, 255)', (new RgbColor(255, 255, 255))->toCss());
        $this->assertSame('rgb(181, 55, 23)', (new RgbColor(181, 55, 23))->toCss());
    }

    public function testToString(): void
    {
        $c = new RgbColor(181, 55, 23);
        $this->assertSame('rgb(181, 55, 23)', $c->toString());
    }

    public function testToArray(): void
    {
        $this->assertSame([181, 55, 23], (new RgbColor(181, 55, 23))->toArray());
    }
}
