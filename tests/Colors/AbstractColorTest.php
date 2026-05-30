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

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

/**
 * Base test case for all AbstractColor implementations.
 *
 * Most methods declared in AbstractColor are tested here, driven by the shared
 * ColorFixtures dataset.
 */
abstract class AbstractColorTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Format and utilities
    // -------------------------------------------------------------------------

    #[DataProviderExternal(ColorFixtures::class, 'all')]
    public function testToInt(array $fixture): void
    {
        $this->assertSame($fixture['toInt'], $fixture['rgb']->toInt());
    }

    #[DataProviderExternal(ColorFixtures::class, 'all')]
    public function testToHex(array $fixture): void
    {
        $this->assertSame($fixture['hex'], $fixture['rgb']->toHex());
        $this->assertSame('#'.$fixture['hex'], $fixture['rgb']->toHex('#'));
    }

    #[DataProviderExternal(ColorFixtures::class, 'all')]
    public function testLuminance(array $fixture): void
    {
        $this->assertEqualsWithDelta($fixture['luminance'], $fixture['rgb']->luminance(), 0.0001);
    }

    #[DataProviderExternal(ColorFixtures::class, 'all')]
    public function testIsDark(array $fixture): void
    {
        $this->assertSame($fixture['isDark'], $fixture['rgb']->isDark());
    }

    #[DataProviderExternal(ColorFixtures::class, 'all')]
    public function testIsLight(array $fixture): void
    {
        $this->assertSame(!$fixture['isDark'], $fixture['rgb']->isLight());
    }

    #[DataProviderExternal(ColorFixtures::class, 'all')]
    public function testTextColor(array $fixture): void
    {
        $textColor = $fixture['rgb']->textColor();
        if ($fixture['isDark']) {
            $this->assertSame(255, $textColor->red());
            $this->assertSame(255, $textColor->green());
            $this->assertSame(255, $textColor->blue());
        } else {
            $this->assertSame(0, $textColor->red());
            $this->assertSame(0, $textColor->green());
            $this->assertSame(0, $textColor->blue());
        }
    }

    // -------------------------------------------------------------------------
    // Specific tests to implement in child classes
    // -------------------------------------------------------------------------

    abstract public function testToCss(): void;

    abstract public function testToString(): void;

    abstract public function testToArray(): void;
}
