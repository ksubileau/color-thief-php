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
use ColorThief\Colors\HslColor;

class HslColorTest extends AbstractColorTest
{
    protected const FIXTURE_COLORSPACE_KEY = 'hsl';

    protected function toSelf(AbstractColor $color): AbstractColor
    {
        return $color->toHsl();
    }

    public function testConstructorAndAccessors(): void
    {
        $c = new HslColor(180.0, 0.5, 0.75);
        $this->assertSame(180.0, $c->hue());
        $this->assertSame(0.5, $c->saturation());
        $this->assertSame(0.75, $c->lightness());
        $this->assertSame(0, $c->population());
        $this->assertSame(0.0, $c->proportion());
    }

    public function testToCss(): void
    {
        $this->assertSame('hsl(180, 50%, 75%)', (new HslColor(180.0, 0.5, 0.75))->toCss());
        $this->assertSame('hsl(0, 100%, 50%)', (new HslColor(0.0, 1.0, 0.5))->toCss());
        $this->assertSame('hsl(0, 0%, 100%)', (new HslColor(0.0, 0.0, 1.0))->toCss());
        $this->assertSame('hsl(0, 0%, 0%)', (new HslColor(0.0, 0.0, 0.0))->toCss());
        $this->assertSame('hsl(240, 100%, 50%)', (new HslColor(240.0, 1.0, 0.5))->toCss());
        // Rounding: 0.505 → 51%
        $this->assertSame('hsl(120, 51%, 60%)', (new HslColor(120.0, 0.505, 0.6))->toCss());
    }

    public function testToString(): void
    {
        $c = new HslColor(120.0, 0.505, 0.6);
        $this->assertSame('hsl(120, 51%, 60%)', $c->toCss());
    }

    public function testToArray(): void
    {
        $this->assertSame([180.0, 0.5, 0.75], (new HslColor(180.0, 0.5, 0.75))->toArray());
    }
}
