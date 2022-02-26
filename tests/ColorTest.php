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

namespace ColorThief\Tests;

use ColorThief\Color;
use ColorThief\Exception\NotSupportedException;

class ColorTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructor(): void
    {
        $c = new Color();
        $this->assertEquals(0, $c->getRed());
        $this->assertEquals(0, $c->getGreen());
        $this->assertEquals(0, $c->getBlue());

        $c = new Color(181, 55, 23);
        $this->assertEquals(181, $c->getRed());
        $this->assertEquals(55, $c->getGreen());
        $this->assertEquals(23, $c->getBlue());
    }

    public function testGetInt(): void
    {
        $c = new Color();
        $i = $c->getInt();
        $this->assertIsInt($i);
        $this->assertEquals(0, $i);

        $c = new Color(255, 255, 255);
        $i = $c->getInt();
        $this->assertIsInt($i);
        $this->assertEquals(16777215, $i);

        $c = new Color(255, 255, 255);
        $i = $c->getInt();
        $this->assertIsInt($i);
        $this->assertEquals(16777215, $i);

        $c = new Color(181, 55, 23);
        $i = $c->getInt();
        $this->assertIsInt($i);
        $this->assertEquals(11876119, $i);
    }

    public function testGetHex(): void
    {
        $c = new Color();
        $i = $c->getHex();
        $this->assertIsString($i);
        $this->assertEquals('000000', $i);

        $c = new Color(255, 255, 255);
        $i = $c->getHex();
        $this->assertIsString($i);
        $this->assertEquals('ffffff', $i);

        $c = new Color(181, 55, 23);
        $i = $c->getHex();
        $this->assertIsString($i);
        $this->assertEquals('b53717', $i);
    }

    public function testGetArray(): void
    {
        $c = new Color();
        $i = $c->getArray();
        $this->assertIsArray($i);
        $this->assertEquals([0, 0, 0], $i);

        $c = new Color(255, 255, 255);
        $i = $c->getArray();
        $this->assertIsArray($i);
        $this->assertEquals([255, 255, 255], $i);

        $c = new Color(181, 55, 23);
        $i = $c->getArray();
        $this->assertIsArray($i);
        $this->assertEquals([181, 55, 23], $i);
    }

    public function testGetRgb(): void
    {
        $c = new Color();
        $i = $c->getRgb();
        $this->assertIsString($i);
        $this->assertEquals('rgb(0, 0, 0)', $i);

        $c = new Color(255, 255, 255);
        $i = $c->getRgb();
        $this->assertIsString($i);
        $this->assertEquals('rgb(255, 255, 255)', $i);

        $c = new Color(181, 55, 23);
        $i = $c->getRgb();
        $this->assertIsString($i);
        $this->assertEquals('rgb(181, 55, 23)', $i);
    }

    public function testFormatUnknown(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Color format (xxxxxxxxxxx) is not supported.');

        $c = new Color();
        $c->format('xxxxxxxxxxx');
    }
}
