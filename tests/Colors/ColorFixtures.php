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

class ColorFixtures
{
    /**
     * @return array{hex:string,toInt:int,luminance:float,isDark:bool,rgb:RgbColor}
     */
    private static function buildColor(
        string $hex,
        int $toInt,
        float $luminance,
        bool $isDark,
        RgbColor $rgb,
    ): array {
        return compact('hex', 'toInt', 'luminance', 'isDark', 'rgb');
    }

    /** @return iterable<string, array{array}> */
    public static function all(): iterable
    {
        yield 'black' => [self::buildColor(
            hex: '000000', toInt: 0, luminance: 0.0, isDark: true,
            rgb: new RgbColor(0, 0, 0, 42, 0.42),
        )];
        yield 'white' => [self::buildColor(
            hex: 'ffffff', toInt: 16777215, luminance: 1.0, isDark: false,
            rgb: new RgbColor(255, 255, 255, 42, 0.42),
        )];
        yield 'red' => [self::buildColor(
            hex: 'ff0000', toInt: 16711680, luminance: 0.2126, isDark: false,
            rgb: new RgbColor(255, 0, 0, 42, 0.42),
        )];
        yield 'green' => [self::buildColor(
            hex: '00ff00', toInt: 65280, luminance: 0.7152, isDark: false,
            rgb: new RgbColor(0, 255, 0, 42, 0.42),
        )];
        yield 'blue' => [self::buildColor(
            hex: '0000ff', toInt: 255, luminance: 0.0722, isDark: true,
            rgb: new RgbColor(0, 0, 255, 42, 0.42),
        )];
        yield 'indigo' => [self::buildColor(
            hex: '2f3450', toInt: 3093584, luminance: 0.036395, isDark: true,
            rgb: new RgbColor(47, 52, 80, 42, 0.42),
        )];
    }
}
