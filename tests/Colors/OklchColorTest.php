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

use ColorThief\Colors\AbstractColor;
use ColorThief\Colors\OklchColor;

class OklchColorTest extends AbstractColorTest
{
    protected const FIXTURE_COLORSPACE_KEY = 'oklch';

    protected function toSelf(AbstractColor $color): AbstractColor
    {
        return $color->toOklch();
    }

    public function testConstructorAndAccessors(): void
    {
        $c = new OklchColor(0.7, 0.15, 270.0);
        $this->assertSame(0.7, $c->lightness());
        $this->assertSame(0.15, $c->chroma());
        $this->assertSame(270.0, $c->hue());
        $this->assertSame(0, $c->population());
        $this->assertSame(0.0, $c->proportion());
    }

    public function testToCss(): void
    {
        $this->assertSame('oklch(0.7000 0.1500 270.00)', (new OklchColor(0.7, 0.15, 270.0))->toCss());
        $this->assertSame('oklch(0.0000 0.0000 0.00)', (new OklchColor(0.0, 0.0, 0.0))->toCss());
        $this->assertSame('oklch(1.0000 0.0000 0.00)', (new OklchColor(1.0, 0.0, 0.0))->toCss());
        $this->assertSame('oklch(0.5000 0.2000 120.00)', (new OklchColor(0.5, 0.2, 120.0))->toCss());
    }

    public function testToString(): void
    {
        $c = new OklchColor(0.7, 0.15, 270.0);
        $this->assertSame('oklch(0.7000 0.1500 270.00)', $c->toString());
    }

    public function testToArray(): void
    {
        $this->assertSame([0.7, 0.15, 270.0], (new OklchColor(0.7, 0.15, 270.0))->toArray());
    }
}
