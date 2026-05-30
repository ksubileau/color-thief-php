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
use ColorThief\Colors\CmykColor;

class CmykColorTest extends AbstractColorTest
{
    protected const FIXTURE_COLORSPACE_KEY = 'cmyk';

    protected function toSelf(AbstractColor $color): AbstractColor
    {
        return $color->toCmyk();
    }

    public function testConstructorAndAccessors(): void
    {
        $c = new CmykColor(0.25, 0.5, 0.75, 0.1);
        $this->assertSame(0.25, $c->cyan());
        $this->assertSame(0.5, $c->magenta());
        $this->assertSame(0.75, $c->yellow());
        $this->assertSame(0.1, $c->black());
        $this->assertSame(0, $c->population());
        $this->assertSame(0.0, $c->proportion());
    }

    public function testToCss(): void
    {
        $this->assertSame('rgb(255, 0, 0)', (new CmykColor(0.0, 1.0, 1.0, 0.0))->toCss());
        $this->assertSame('rgb(0, 0, 0)', (new CmykColor(0.0, 0.0, 0.0, 1.0))->toCss());
        $this->assertSame('rgb(255, 255, 255)', (new CmykColor(0.0, 0.0, 0.0, 0.0))->toCss());
    }

    public function testToString(): void
    {
        $this->assertSame('cmyk(25%, 50%, 75%, 10%)', (new CmykColor(0.25, 0.5, 0.75, 0.1))->toString());
        $this->assertSame('cmyk(0%, 100%, 100%, 0%)', (new CmykColor(0.0, 1.0, 1.0, 0.0))->toString());
        $this->assertSame('cmyk(0%, 0%, 0%, 100%)', (new CmykColor(0.0, 0.0, 0.0, 1.0))->toString());
        $this->assertSame('cmyk(100%, 100%, 0%, 0%)', (new CmykColor(1.0, 1.0, 0.0, 0.0))->toString());
        // Rounding: 0.505 → 51%
        $this->assertSame('cmyk(51%, 50%, 0%, 0%)', (new CmykColor(0.505, 0.5, 0.0, 0.0))->toString());
    }

    public function testToArray(): void
    {
        $this->assertSame([0.25, 0.5, 0.75, 0.1], (new CmykColor(0.25, 0.5, 0.75, 0.1))->toArray());
    }
}
