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
use ColorThief\Colors\HsvColor;

class HsvColorTest extends AbstractColorTest
{
    protected const FIXTURE_COLORSPACE_KEY = 'hsv';

    protected function toSelf(AbstractColor $color): AbstractColor
    {
        return $color->toHsv();
    }

    public function testConstructorAndAccessors(): void
    {
        $c = new HsvColor(90.0, 0.8, 0.6);
        $this->assertSame(90.0, $c->hue());
        $this->assertSame(0.8, $c->saturation());
        $this->assertSame(0.6, $c->value());
        $this->assertSame(0, $c->population());
        $this->assertSame(0.0, $c->proportion());
    }

    public function testToCss(): void
    {
        $this->assertSame('rgb(255, 0, 0)', (new HsvColor(0.0, 1.0, 1.0))->toCss());
        $this->assertSame('rgb(0, 0, 0)', (new HsvColor(0.0, 0.0, 0.0))->toCss());
        $this->assertSame('rgb(255, 255, 255)', (new HsvColor(0.0, 0.0, 1.0))->toCss());
    }

    public function testToString(): void
    {
        $this->assertSame('hsv(90, 80%, 60%)', (new HsvColor(90.0, 0.8, 0.6))->toString());
        $this->assertSame('hsv(0, 100%, 100%)', (new HsvColor(0.0, 1.0, 1.0))->toString());
        $this->assertSame('hsv(0, 0%, 100%)', (new HsvColor(0.0, 0.0, 1.0))->toString());
        $this->assertSame('hsv(240, 100%, 100%)', (new HsvColor(240.0, 1.0, 1.0))->toString());
        // Rounding: 0.505 → 51%
        $this->assertSame('hsv(120, 51%, 80%)', (new HsvColor(120.0, 0.505, 0.8))->toString());
    }

    public function testToArray(): void
    {
        $this->assertSame([90.0, 0.8, 0.6], (new HsvColor(90.0, 0.8, 0.6))->toArray());
    }
}
