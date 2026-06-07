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
use ColorThief\Colors\AbstractColor;
use ColorThief\Colors\RgbColor;
use ColorThief\ColorSpace;
use ColorThief\ColorSwatches;
use ColorThief\ColorThief;
use ColorThief\Exception\InvalidArgumentException;
use ColorThief\Image\Adapter\AdapterInterface;
use ColorThief\Image\PixelColor;
use PHPUnit\Framework\Attributes\DataProvider;

class ColorThiefTest extends \PHPUnit\Framework\TestCase
{
    private ColorThief $colorThief;

    protected function setUp(): void
    {
        $this->colorThief = new ColorThief();
    }

    /**
     * Asserts that two palette colors are equal.
     */
    private function assertColorEquals(
        $expectedColor,
        AbstractColor $actualColor,
        string $messagePrefix = '',
        int $componentDelta = 2,
        float $populationDeltaPercent = 0.15,
        float $proportionDelta = 0.0002,
    ): void {
        $this->assertEqualsWithDelta($expectedColor->toArray(), $actualColor->toArray(), $componentDelta, "{$messagePrefix}components mismatch");
        $this->assertEqualsWithDelta($expectedColor->population(), $actualColor->population(), round($populationDeltaPercent * $expectedColor->population()), "{$messagePrefix}population mismatch");
        $this->assertEqualsWithDelta($expectedColor->proportion(), $actualColor->proportion(), $proportionDelta, "{$messagePrefix}proportion mismatch");
    }

    /**
     * Asserts that two palettes are equal.
     */
    private function assertPaletteEquals(ColorPalette $expected, ColorPalette $actual): void
    {
        $this->assertEquals($expected->count(), $actual->count(), 'Palette color count mismatch');

        foreach ($expected as $index => $expectedColor) {
            $this->assertColorEquals($expectedColor, $actual[$index], "Color #{$index}: ");
        }
    }

    /**
     * Asserts that all expected swatches are present and RGB-close in actual swatches.
     */
    private function assertSwatchesMatch(ColorSwatches $expected, ColorSwatches $actual, int $componentDelta = 3): void
    {
        foreach (['vibrant', 'muted', 'darkVibrant', 'darkMuted', 'lightVibrant', 'lightMuted'] as $role) {
            /** @var ?RgbColor $expectedColor */
            $expectedColor = $expected->{$role};
            /** @var ?RgbColor $actualColor */
            $actualColor = $actual->{$role};

            if (null === $expectedColor) {
                $this->assertNull($actualColor, "Swatch '{$role}' should be null.");
                continue;
            }

            $this->assertNotNull($actualColor, "Swatch '{$role}' should not be null.");
            $this->assertColorEquals($expectedColor, $actualColor, "Swatch '{$role}': ");
        }
    }

    public static function provideImageDominantColor(): array
    {
        return [
            'rails' => [
                '/images/rails_600x406.gif',
                new RgbColor(158, 111, 87, 12066, 0.49532),
            ],
            'rails | RGB' => [
                '/images/rails_600x406.gif',
                new RgbColor(88, 70, 80, 14734, 0.60484),
                ColorSpace::Rgb,
            ],
            'field' => [
                '/images/field_1024x683.jpg',
                new RgbColor(110, 175, 222, 36867, 0.52712),
            ],
            'field | RGB' => [
                '/images/field_1024x683.jpg',
                new RgbColor(107, 172, 222, 35527, 0.50796),
                ColorSpace::Rgb,
            ],
            'covers - cmyk' => [
                '/images/covers_cmyk_PR37.jpg',
                new RgbColor(107, 228, 237, 16799, 0.69995),
            ],
            'single color' => [
                '/images/single_color_PR41.png',
                new RgbColor(181, 230, 29, 36000, 1.0),
            ],
            'donuts | WebP' => [  // WebP image
                '/images/donuts_PR45.webp',
                new RgbColor(215, 171, 113, 12829, 0.53468),
            ],
            'vegetables | area' => [  // Area targeting
                '/images/vegetables_1500x995.png',
                new RgbColor(63, 112, 24, 2404, 0.87101),
                ColorSpace::Rgb,
                ['x' => 670, 'y' => 215, 'w' => 230, 'h' => 120],
            ],
            'vegetables | area_partial' => [  // Area targeting with default values for y and width.
                '/images/vegetables_1500x995.png',
                new RgbColor(54, 60, 33, 5608, 0.56080),
                ColorSpace::Rgb,
                ['x' => 1300, 'h' => 500],
            ],
        ];
    }

    public static function provideImageColorPalette(): array
    {
        return [
            'rails' => [
                '/images/rails_600x406.gif',
                new ColorPalette(
                    new RgbColor(78, 64, 82, 3536, 0.43547),
                    new RgbColor(148, 100, 80, 2894, 0.3564),
                    new RgbColor(205, 162, 118, 965, 0.11884),
                    new RgbColor(234, 217, 179, 360, 0.04433),
                    new RgbColor(114, 138, 140, 66, 0.00813),
                    new RgbColor(118, 118, 136, 158, 0.01946),
                    new RgbColor(78, 45, 43, 137, 0.01687),
                    new RgbColor(182, 171, 181, 4, 0.00049),
                ),
            ],
            'rails | RGB' => [
                '/images/rails_600x406.gif',
                new ColorPalette(
                    new RgbColor(210, 170, 127, 1561, 0.19224),
                    new RgbColor(88, 69, 81, 4559, 0.56145),
                    new RgbColor(158, 113, 84, 1696, 0.20887),
                    new RgbColor(157, 190, 175, 43, 0.00530),
                    new RgbColor(107, 119, 129, 115, 0.01416),
                    new RgbColor(82, 48, 33, 115, 0.01416),
                    new RgbColor(52, 136, 211, 11, 0.00135),
                    new RgbColor(29, 68, 84, 11, 0.00135),
                    new RgbColor(120, 124, 101, 6, 0.00074),
                    new RgbColor(212, 76, 60, 3, 0.00037),
                ),
                ColorSpace::Rgb,
            ],
            'vegetables' => [
                '/images/vegetables_1500x995.png',
                new ColorPalette(
                    new RgbColor(85, 74, 18, 9843, 0.19813),
                    new RgbColor(124, 118, 132, 3664, 0.07375),
                    new RgbColor(230, 212, 188, 16099, 0.32406),
                    new RgbColor(41, 20, 0, 4615, 0.0929),
                    new RgbColor(125, 125, 63, 6438, 0.12959),
                    new RgbColor(149, 129, 0, 1758, 0.03539),
                    new RgbColor(162, 189, 140, 3441, 0.06926),
                    new RgbColor(153, 184, 179, 904, 0.0182),
                    new RgbColor(186, 155, 107, 2778, 0.05592),
                    new RgbColor(27, 22, 28, 139, 0.0028),
                ),
            ],
            'covers - cmyk' => [
                '/images/covers_cmyk_PR37.jpg',
                new ColorPalette(
                    new RgbColor(223, 75, 104, 4130, 0.17203),
                    new RgbColor(133, 235, 249, 11750, 0.48958),
                    new RgbColor(0, 55, 117, 2096, 0.08733),
                    new RgbColor(208, 224, 130, 2373, 0.09888),
                    new RgbColor(243, 116, 181, 539, 0.02245),
                    new RgbColor(92, 166, 255, 2181, 0.09088),
                    new RgbColor(52, 102, 254, 436, 0.01816),
                    new RgbColor(57, 145, 204, 495, 0.02063),
                ),
                ColorSpace::Oklch,
                10,
            ],
            'donuts | WebP' => [
                '/images/donuts_PR45.webp',
                new ColorPalette(
                    new RgbColor(226, 191, 139, 9049, 0.37714),
                    new RgbColor(178, 137, 178, 3215, 0.13399),
                    new RgbColor(77, 23, 8, 2296, 0.09569),
                    new RgbColor(140, 64, 0, 3104, 0.12937),
                    new RgbColor(104, 206, 196, 2336, 0.09736),
                    new RgbColor(165, 115, 64, 2567, 0.10699),
                    new RgbColor(244, 118, 6, 1213, 0.05055),
                    new RgbColor(52, 24, 48, 214, 0.00892),
                ),
                ColorSpace::Oklch,
                10,
            ],
        ];
    }

    public static function provideImageColorSwatches(): array
    {
        return [
            'rails' => [
                '/images/rails_600x406.gif',
                new ColorSwatches(
                    vibrant: new RgbColor(216, 100, 74, 10, 0.00037),
                    muted: new RgbColor(164, 115, 87, 7219, 0.29634),
                    darkVibrant: new RgbColor(64, 70, 135, 4, 0.00016),
                    darkMuted: new RgbColor(74, 58, 76, 8496, 0.34876),
                    lightVibrant: null,
                    lightMuted: new RgbColor(233, 218, 179, 986, 0.04048),
                ),
                ColorSpace::Oklch,
                10,
            ],
            'vegetables' => [
                '/images/vegetables_1500x995.png',
                new ColorSwatches(
                    vibrant: new RgbColor(149, 129, 0, 1758, 0.03539),
                    muted: new RgbColor(125, 125, 63, 6438, 0.12959),
                    darkVibrant: null,
                    darkMuted: new RgbColor(40, 17, 5, 3744, 0.07536),
                    lightVibrant: new RgbColor(218, 192, 106, 535, 0.01076),
                    lightMuted: new RgbColor(233, 218, 197, 13617, 0.27410),
                ),
            ],
            'covers - cmyk' => [
                '/images/covers_cmyk_PR37.jpg',
                new ColorSwatches(
                    vibrant: new RgbColor(243, 82, 117, 3664, 0.15267),
                    muted: new RgbColor(168, 194, 196, 28, 0.00117),
                    darkVibrant: new RgbColor(14, 40, 196, 796, 0.03317),
                    darkMuted: new RgbColor(0, 43, 73, 1179, 0.04913),
                    lightVibrant: new RgbColor(208, 224, 130, 2373, 0.09888),
                    lightMuted: new RgbColor(179, 209, 186, 1, 0.00004),
                ),
                ColorSpace::Oklch,
                10,
            ],
            'single color' => [
                '/images/single_color_PR41.png',
                new ColorSwatches(
                    vibrant: null,
                    muted: null,
                    darkVibrant: null,
                    darkMuted: null,
                    lightVibrant: new RgbColor(181, 230, 29, 12000, 1.0),
                    lightMuted: null,
                ),
            ],
        ];
    }

    public static function provideInvalidConstructorArguments(): array
    {
        return [
            'quality too low' => [0, 250, 125, 0.0, 'The quality argument'],
            'white threshold too low' => [10, -1, 125, 0.0, 'The whiteThreshold argument'],
            'white threshold too high' => [10, 256, 125, 0.0, 'The whiteThreshold argument'],
            'alpha threshold too low' => [10, 250, -1, 0.0, 'The alphaThreshold argument'],
            'alpha threshold too high' => [10, 250, 256, 0.0, 'The alphaThreshold argument'],
            'minimum saturation too low' => [10, 250, 125, -0.01, 'The minSaturation argument'],
            'minimum saturation equal to one' => [10, 250, 125, 1.0, 'The minSaturation argument'],
            'minimum saturation too high' => [10, 250, 125, 1.01, 'The minSaturation argument'],
        ];
    }

    #[DataProvider('provideInvalidConstructorArguments')]
    public function testConstructorRejectsInvalidArguments(int $quality, int $whiteThreshold, int $alphaThreshold, float $minSaturation, string $expectedMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        new ColorThief($quality, $whiteThreshold, $alphaThreshold, $minSaturation);
    }

    #[DataProvider('provideImageDominantColor')]
    public function testDominantColor(string $image, RgbColor $expectedColor, ?ColorSpace $colorSpace = null, ?array $area = null): void
    {
        $config = [];
        if (null !== $colorSpace) {
            $config['colorSpace'] = $colorSpace;
        }

        $dominantColor = $this->colorThief->with(...$config)->getColor(__DIR__.$image, $area);

        $this->assertInstanceOf(RgbColor::class, $dominantColor);
        $this->assertColorEquals($expectedColor, $dominantColor);
    }

    /**
     * Asserts that the response palette includes the requested number of colors.
     */
    public function testPaletteColorCount(): void
    {
        $testWith = [2, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 20];
        foreach ($testWith as $numColors) {
            $palette = $this->colorThief
                ->with(quality: 200)
                ->getPalette(__DIR__.'/images/vegetables_1500x995.png', $numColors);

            $this->assertCount($numColors, $palette);
        }
    }

    #[DataProvider('provideImageColorPalette')]
    public function testPalette(string $image, ColorPalette $expectedPalette, ?ColorSpace $colorSpace = null, int $quality = 30, ?array $area = null): void
    {
        $numColors = \count($expectedPalette);

        $config = ['quality' => $quality];
        if (null !== $colorSpace) {
            $config['colorSpace'] = $colorSpace;
        }

        $palette = $this->colorThief
            ->with(...$config)
            ->getPalette(__DIR__.$image, $numColors, $area);

        $this->assertCount($numColors, $palette);
        $this->assertPaletteEquals($expectedPalette, $palette);
    }

    #[DataProvider('provideImageColorPalette')]
    public function testPaletteBinaryString(string $image, ColorPalette $expectedPalette, ?ColorSpace $colorSpace = null, int $quality = 30, ?array $area = null): void
    {
        $numColors = \count($expectedPalette);
        $image = file_get_contents(__DIR__.$image);

        $config = ['quality' => $quality];
        if (null !== $colorSpace) {
            $config['colorSpace'] = $colorSpace;
        }

        $palette = $this->colorThief
            ->with(...$config)
            ->getPalette($image, $numColors, $area);

        $this->assertCount($numColors, $palette);
        $this->assertPaletteEquals($expectedPalette, $palette);
    }

    #[DataProvider('provideImageColorSwatches')]
    public function testSwatches(string $image, ColorSwatches $expectedSwatches, ?ColorSpace $colorSpace = null, int $quality = 30, ?array $area = null): void
    {
        $config = ['quality' => $quality];
        if (null !== $colorSpace) {
            $config['colorSpace'] = $colorSpace;
        }

        $swatches = $this->colorThief
            ->with(...$config)
            ->getSwatches(__DIR__.$image, $area);

        $this->assertSwatchesMatch($expectedSwatches, $swatches);
    }

    public function testGetPaletteWithCustomAdapter(): void
    {
        $adapter = $this->createMock(AdapterInterface::class);

        // Stub adapter that simulates a monochrome image
        $adapter->method('loadFromPath')->willReturnSelf();
        $adapter->method('getWidth')->willReturn(2);
        $adapter->method('getHeight')->willReturn(2);
        $adapter->method('getPixelColor')->willReturn(
            new PixelColor(red: 24, green: 60, blue: 100, alpha: 255),
            new PixelColor(red: 24, green: 60, blue: 100, alpha: 255),
            new PixelColor(red: 56, green: 20, blue: 14, alpha: 255),
            new PixelColor(red: 56, green: 20, blue: 14, alpha: 255)
        );

        $palette = $this->colorThief
            ->with(
                quality: 1,
                preferredAdapter: $adapter,
            )->getPalette(__DIR__.'/images/rails_600x406.gif', 2);

        $this->assertPaletteEquals(
            new ColorPalette(
                new RgbColor(24, 60, 100, 2, 0.5),
                new RgbColor(56, 20, 14, 2, 0.5),
            ),
            $palette
        );
    }

    public function testGetPaletteWithTooFewColors(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The number of palette colors');

        $this->colorThief->getPalette('foo.jpg', 1);
    }

    public function testGetPaletteWithTooManyColors(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The number of palette colors');

        $this->colorThief->getPalette('foo.jpg', 21);
    }

    public function testGetPaletteWithSingleColorImage(): void
    {
        $palette = $this->colorThief->getPalette(__DIR__.'/images/single_color_PR41.png');
        $this->assertPaletteEquals(
            new ColorPalette(new RgbColor(181, 230, 29, 36000, 1)),
            $palette
        );
    }

    public function testGetPaletteWithWhiteImage(): void
    {
        $palette = $this->colorThief->getPalette(__DIR__.'/images/white.png');
        $this->assertEquals(
            new ColorPalette(new RgbColor(255, 255, 255, 16000, 1)),
            $palette
        );
    }

    public function testGetPaletteWithTransparentImage(): void
    {
        $palette = $this->colorThief->getPalette(__DIR__.'/images/transparent.png');
        $this->assertEquals(
            new ColorPalette(new RgbColor(0, 0, 0, 16000, 1)),
            $palette
        );
    }
}
