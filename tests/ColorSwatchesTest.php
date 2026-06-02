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

use ColorThief\ColorPalette;
use ColorThief\Colors\RgbColor;
use ColorThief\ColorSwatches;
use PHPUnit\Framework\Attributes\DataProvider;

class ColorSwatchesTest extends \PHPUnit\Framework\TestCase
{
    public static function provideSwatchAssignments(): iterable
    {
        yield 'empty palette' => [
            [],
            new ColorSwatches(),
        ];

        $vibrant = new RgbColor(200, 100, 30, 1000, 0.4);
        $muted = new RgbColor(100, 120, 160, 800, 0.32);
        $darkVibrant = new RgbColor(120, 20, 20, 600, 0.24);
        yield 'small palette with one color per swatch role' => [
            [$vibrant, $muted, $darkVibrant],
            new ColorSwatches(vibrant: $vibrant, muted: $muted, darkVibrant: $darkVibrant),
        ];

        $single = new RgbColor(200, 100, 30, 500, 1.0);
        yield 'single color' => [
            [$single],
            new ColorSwatches(vibrant: $single),
        ];

        $darkMuted = new RgbColor(30, 40, 20, 800, 0.4);
        $darkRed = new RgbColor(60, 20, 20, 1000, 0.5);
        $darkPurple = new RgbColor(40, 30, 50, 600, 0.3);
        yield 'dark-only palette with low chroma' => [
            [$darkRed, $darkMuted, $darkPurple],
            new ColorSwatches(darkMuted: $darkMuted),
        ];

        $mutedLight = new RgbColor(220, 180, 130, 1000, 0.4);
        $lightMuted = new RgbColor(180, 220, 200, 800, 0.32);
        $lightWarm = new RgbColor(230, 210, 190, 600, 0.24);
        $lightSky = new RgbColor(200, 230, 255, 400, 0.16);
        yield 'light-only palette' => [
            [$mutedLight, $lightMuted, $lightWarm, $lightSky],
            new ColorSwatches(muted: $mutedLight, lightMuted: $lightMuted),
        ];

        $lightMutedGrey = new RgbColor(200, 200, 200, 1000, 0.5);
        $mutedGrey = new RgbColor(100, 100, 100, 800, 0.4);
        $darkMutedGrey = new RgbColor(50, 50, 50, 600, 0.3);
        yield 'greyscale palette' => [
            [$lightMutedGrey, $mutedGrey, $darkMutedGrey],
            new ColorSwatches(muted: $mutedGrey, darkMuted: $darkMutedGrey, lightMuted: $lightMutedGrey),
        ];

        // rgb(65, 189, 243) match lightVibrant, vibrant, lightMuted and muted, but is closer to lightVibrant
        $lightVibrant = new RgbColor(65, 189, 243, 5000, 0.5);
        $vibrantConfl = new RgbColor(70, 180, 240, 2000, 0.2);
        $darkMutedConfl = new RgbColor(40, 30, 20, 1000, 0.1);
        yield 'conflicting colors' => [
            [$lightVibrant, $vibrantConfl, $darkMutedConfl],
            new ColorSwatches(vibrant: $vibrantConfl, darkMuted: $darkMutedConfl, lightVibrant: $lightVibrant),
        ];

        // rgb(120, 20, 20) match both darkVibrant and darkMuted, but is closer to darkVibrant,
        // and there is no other darkMuted candidate.
        $darkVibrantOnly = new RgbColor(120, 20, 20, 1000, 0.5);
        $vibrantFb = new RgbColor(200, 100, 30, 800, 0.4);
        $mutedFb = new RgbColor(100, 120, 160, 600, 0.3);
        yield 'conflicting colors without fallback candidate' => [
            [$darkVibrantOnly, $vibrantFb, $mutedFb],
            new ColorSwatches(vibrant: $vibrantFb, muted: $mutedFb, darkVibrant: $darkVibrantOnly),
        ];

        // Full palette from a real image
        $sandy = new RgbColor(193, 178, 115, 5330, 0.19846589216562407);
        $darkMuted16 = new RgbColor(44, 32, 23, 4147, 0.154416145367888);
        $vibrant16 = new RgbColor(168, 105, 62, 3098, 0.1153559725945785);
        $muted16 = new RgbColor(153, 160, 187, 1123, 0.041815609174858506);
        $darkOlive = new RgbColor(79, 100, 70, 1994, 0.07424784033363122);
        $lightMuted16 = new RgbColor(113, 213, 232, 7310, 0.2721924337205839);
        $lightVibrant16 = new RgbColor(163, 220, 77, 847, 0.031538576109621685);
        $teal = new RgbColor(71, 151, 165, 1590, 0.059204647006255585);
        $lightBlue = new RgbColor(198, 221, 229, 1013, 0.03771969019958296);
        $darkPurple16 = new RgbColor(41, 35, 45, 100, 0.0037235627047959487);
        $lightGreen = new RgbColor(145, 215, 148, 196, 0.00729818290140006);
        $veryDark = new RgbColor(23, 30, 30, 16, 0.0005957700327673518);
        $lightPeach = new RgbColor(224, 201, 186, 38, 0.0014149538278224606);
        $lightCyan = new RgbColor(180, 248, 244, 51, 0.0018990169794459338);
        $darkVibrant16 = new RgbColor(105, 3, 4, 1, 0.00003723562704795949);
        $skyBlue = new RgbColor(65, 189, 243, 2, 0.00007447125409591897);
        yield 'full palette' => [
            [
                $sandy, $darkMuted16, $vibrant16, $muted16, $darkOlive, $lightMuted16,
                $lightVibrant16, $teal, $lightBlue, $darkPurple16, $lightGreen,
                $veryDark, $lightPeach, $lightCyan, $darkVibrant16, $skyBlue,
            ],
            new ColorSwatches($vibrant16, $muted16, $darkVibrant16, $darkMuted16, $lightVibrant16, $lightMuted16),
        ];
    }

    #[DataProvider('provideSwatchAssignments')]
    public function testSwatchAssignmentFromPalette(array $palette, ColorSwatches $expected): void
    {
        $this->assertEquals($expected, ColorSwatches::fromPalette(new ColorPalette(...$palette)));
    }
}
