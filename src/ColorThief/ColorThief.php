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

namespace ColorThief;

use ColorThief\Colors\RgbColor;
use ColorThief\Exception\InvalidArgumentException;
use ColorThief\Image\Adapter\AdapterInterface;
use ColorThief\Image\ImageLoader;
use ColorThief\Image\PixelColor;
use ColorThief\Internal\Mmcq;

/**
 * Grabs the dominant color or a representative color palette from an image.
 *
 * This class requires one of the supported image extensions to be installed on
 * the server: GD, Imagick, or Gmagick.
 *
 * It's a PHP port of the Color Thief JavaScript library
 * (http://github.com/lokesh/color-thief), using the MMCQ
 * (modified median cut quantization) algorithm from
 * the Leptonica library (http://www.leptonica.com/).
 *
 * By Kevin Subileau - http://www.kevinsubileau.fr
 *
 * Based on the work done by Lokesh Dhakar - http://www.lokeshdhakar.com
 * and Nick Rabinowitz
 *
 * Thanks
 * ------
 * * Lokesh Dhakar - For creating the original project.
 * * Nick Rabinowitz - For creating quantize.js.
 */
readonly class ColorThief
{
    /**
     * Create a new ColorThief instance with the given configuration.
     *
     * @param int                          $quality          Sampling quality. 1 is the highest quality. There is a trade-off between quality and speed.
     *                                                       It determines how many pixels are skipped before the next one is sampled.
     *                                                       We rarely need to sample every single pixel in the image to get good results.
     *                                                       The bigger the number, the faster the palette generation but the greater the
     *                                                       likelihood that colors will be missed.
     *                                                       Default: 10.
     * @param int                          $whiteThreshold   Brightness threshold used to skip near-white pixels. Pixels with red, green, and blue
     *                                                       values greater than this threshold are ignored.
     *                                                       Range: 0-255. Set to 255 to disable the white-pixel filter.
     *                                                       Default: 250.
     * @param int                          $alphaThreshold   Transparency threshold used to skip transparent pixels.
     *                                                       Alpha values are in the 0-255 range, where 0 is fully transparent and 255 is fully opaque.
     *                                                       Pixels with an alpha value lower than this threshold are ignored.
     *                                                       Set to 0 to disable alpha filtering.
     *                                                       Default: 125.
     * @param float                        $minSaturation    Minimum saturation ratio (from 0 inclusive to 1 exclusive) used to skip low-saturation pixels.
     *                                                       Set to 0 to disable saturation filtering.
     *                                                       Default: 0.
     * @param ColorSpace                   $colorSpace       Color space used internally for quantization and palette extraction.
     *                                                       Choosing OKLCH produces more perceptually uniform palettes, while RGB keeps the
     *                                                       computation closest to raw pixel data.
     *                                                       Default: ColorSpace::Oklch.
     * @param AdapterInterface|string|null $preferredAdapter Optional preferred image adapter used when loading images.
     *                                                       By default, the adapter is automatically chosen depending on the available extensions
     *                                                       and the type of $sourceImage (for example Imagick is used if $sourceImage is an Imagick instance).
     *                                                       You can pass one of the 'Imagick', 'Gmagick' or 'Gd' strings to use the corresponding
     *                                                       underlying image extension, or you can pass an instance of any class implementing
     *                                                       the AdapterInterface interface to use a custom image loader.
     *                                                       Set to null to keep automatic adapter selection.
     *                                                       Default: null.
     */
    public function __construct(
        private int $quality = 10,
        private int $whiteThreshold = 250,
        private int $alphaThreshold = 125,
        private float $minSaturation = 0,
        private ColorSpace $colorSpace = ColorSpace::Oklch,
        private AdapterInterface|string|null $preferredAdapter = null,
    ) {
        if ($this->quality < 1) {
            throw new InvalidArgumentException('The quality argument must be an positive integer.');
        }

        if ($this->whiteThreshold < 0 || $this->whiteThreshold > 255) {
            throw new InvalidArgumentException('The whiteThreshold argument must be an integer between 0 and 255 inclusive.');
        }

        if ($this->alphaThreshold < 0 || $this->alphaThreshold > 255) {
            throw new InvalidArgumentException('The alphaThreshold argument must be an integer between 0 and 255 inclusive.');
        }

        if (!is_finite($this->minSaturation) || $this->minSaturation < 0 || $this->minSaturation >= 1) {
            throw new InvalidArgumentException('The minSaturation argument must be a float between 0 inclusive and 1 exclusive.');
        }
    }

    /**
     * Return a new instance with one or more configuration overrides.
     *
     * Omitted arguments keep the current instance values.
     *
     * @param int                          $quality          Sampling quality. 1 is the highest quality. There is a trade-off between quality and speed.
     *                                                       It determines how many pixels are skipped before the next one is sampled.
     *                                                       We rarely need to sample every single pixel in the image to get good results.
     *                                                       The bigger the number, the faster the palette generation but the greater the
     *                                                       likelihood that colors will be missed.
     * @param int                          $whiteThreshold   Brightness threshold used to skip near-white pixels. Pixels with red, green, and blue
     *                                                       values greater than this threshold are ignored.
     *                                                       Range: 0-255. Set to 255 to disable the white-pixel filter.
     * @param int                          $alphaThreshold   Transparency threshold used to skip transparent pixels.
     *                                                       Alpha values are in the 0-255 range, where 0 is fully transparent and 255 is fully opaque.
     *                                                       Pixels with an alpha value lower than this threshold are ignored.
     *                                                       Set to 0 to disable alpha filtering.
     * @param float                        $minSaturation    Minimum saturation ratio (from 0 inclusive to 1 exclusive) used to skip low-saturation pixels.
     *                                                       Set to 0 to disable saturation filtering.
     * @param ?ColorSpace                  $colorSpace       Color space used internally for quantization and palette extraction.
     *                                                       Choosing OKLCH produces more perceptually uniform palettes, while RGB keeps the
     *                                                       computation closest to raw pixel data.
     * @param AdapterInterface|string|null $preferredAdapter Optional preferred image adapter used when loading images.
     *                                                       By default, the adapter is automatically chosen depending on the available extensions
     *                                                       and the type of $sourceImage (for example Imagick is used if $sourceImage is an Imagick instance).
     *                                                       You can pass one of the 'Imagick', 'Gmagick' or 'Gd' strings to use the corresponding
     *                                                       underlying image extension, or you can pass an instance of any class implementing
     *                                                       the AdapterInterface interface to use a custom image loader.
     *                                                       Set to null to keep automatic adapter selection.
     */
    public function with(
        int $quality = -1,
        int $whiteThreshold = -1,
        int $alphaThreshold = -1,
        float $minSaturation = -1,
        ?ColorSpace $colorSpace = null,
        AdapterInterface|string|null $preferredAdapter = '__UNSET__',
    ): self {
        /** @var list{array{name: string, sentinel: mixed}}|null $metadata */
        static $metadata = null;

        // Get method parameters and their default values
        $metadata ??= (static function () {
            $method = new \ReflectionMethod(self::class, 'with');

            return array_map(
                static fn (\ReflectionParameter $p) => [
                    'name' => $p->getName(),
                    'sentinel' => $p->getDefaultValue(),
                ],
                $method->getParameters(),
            );
        })();

        // Compute property overrides
        $overrides = [];
        foreach (func_get_args() as $i => $value) {
            $param = $metadata[$i];

            // Filter out parameters that were not explicitly set
            // (i.e. that are still equal to their invalid default value, which is used as a sentinel).
            if ($value !== $param['sentinel']) {
                $overrides[$param['name']] = $value;
            }
        }

        // Get final property values
        /** @var array{quality: int} $values */
        $values = array_merge(get_object_vars($this), $overrides);

        return new self(...$values);
    }

    /**
     * Gets the dominant color from the image using the median cut algorithm to cluster similar colors.
     *
     * @param mixed            $sourceImage Path to the image, GD resource, Imagick/Gmagick instance, or image as binary string
     * @param ImageRegion|null $region      An optional rectangular region of the image to restrict color extraction to.
     *                                      When null, the entire image is analyzed.
     */
    public function getColor(mixed $sourceImage, ?ImageRegion $region = null): ?RgbColor
    {
        $palette = $this->getPalette($sourceImage, 5, $region);

        if ($palette->isEmpty()) {
            return null;
        }

        return $palette[0];
    }

    /**
     * Get semantic swatches (Vibrant, Muted, etc.) from an image.
     *
     * @param mixed            $sourceImage Path to the image, GD resource, Imagick/Gmagick instance, or image as binary string
     * @param ImageRegion|null $region      An optional rectangular region of the image to restrict color extraction to.
     *                                      When null, the entire image is analyzed.
     *
     * @return ColorSwatches a map of semantic swatch roles to their best-matching color (or null if no match)
     */
    public function getSwatches(
        mixed $sourceImage,
        ?ImageRegion $region = null,
    ): ColorSwatches {
        $palette = $this->getPalette($sourceImage, 16, $region);

        return ColorSwatches::fromPalette($palette);
    }

    /**
     * Gets a palette of dominant colors from the image using the median cut algorithm to cluster similar colors.
     *
     * @param mixed            $sourceImage Path to the image, GD resource, Imagick/Gmagick instance, or image as binary string
     * @param int              $colorCount  it determines the size of the palette; the number of colors returned
     * @param ImageRegion|null $region      An optional rectangular region of the image to restrict color extraction to.
     *                                      When null, the entire image is analyzed.
     *
     * @phpstan-return ColorPalette<RgbColor>
     */
    public function getPalette(
        mixed $sourceImage,
        int $colorCount = 10,
        ?ImageRegion $region = null,
    ): ColorPalette {
        if ($colorCount < 2 || $colorCount > 20) {
            throw new InvalidArgumentException('The number of palette colors must be between 2 and 20 inclusive.');
        }

        /** @var array<int, int> $histo */
        $histo = [];
        /** @var array<int, int> $distinctColors */
        $distinctColors = [];

        // Load image histogram and track up to $colorCount + 1 distinct 8-bit colors.
        $numPixelsAnalyzed = $this->loadImage($sourceImage, $this->quality, $histo, $region, $colorCount + 1, $distinctColors, $this->whiteThreshold, $this->alphaThreshold, $this->minSaturation);

        if (0 === $numPixelsAnalyzed) {
            $numPixelsAnalyzed = self::loadImage($sourceImage, $this->quality, $histo, $region, $colorCount + 1, $distinctColors, 255, $this->alphaThreshold, $this->minSaturation);
        }
        if (0 === $numPixelsAnalyzed) {
            $numPixelsAnalyzed = self::loadImage($sourceImage, $this->quality, $histo, $region, $colorCount + 1, $distinctColors, 255, 0, $this->minSaturation);
        }

        // If the number of distinct 8-bit colors is at most $colorCount, build the palette
        // directly from the exact pixel colors — no quantization needed.
        if (\count($distinctColors) <= $colorCount) {
            arsort($distinctColors);
            $palette = array_map(
                static fn (int $colorKey, int $population): RgbColor => new RgbColor(
                    red: ($colorKey >> 16) & 0xFF,
                    green: ($colorKey >> 8) & 0xFF,
                    blue: $colorKey & 0xFF,
                    population: $population,
                ),
                array_keys($distinctColors),
                $distinctColors,
            );
        } else {
            // Send histogram to quantize function which clusters values
            // using median cut algorithm
            $paletteData = Mmcq::quantize($numPixelsAnalyzed, $colorCount, $histo);
            $palette = array_map(function (array $entry): RgbColor {
                $rgb = PixelColor::fromColorSpace($this->colorSpace, ...$entry['channels']);

                return new RgbColor($rgb->red, $rgb->green, $rgb->blue, $entry['population']);
            }, $paletteData);
        }

        // Compute proportion from the final palette population totals
        $totalPopulation = array_sum(array_map(static fn (RgbColor $c): int => $c->population(), $palette));
        $palette = array_map(
            static fn (RgbColor $c): RgbColor => new RgbColor(
                red: $c->red(),
                green: $c->green(),
                blue: $c->blue(),
                population: $c->population(),
                proportion: $totalPopulation > 0 ? $c->population() / $totalPopulation : 0,
            ),
            $palette,
        );

        return new ColorPalette(...$palette);
    }

    /**
     * @param array<int, int> $histo             5-bit histogram (bucket index → pixel count) populated during sampling
     * @param int             $maxDistinctColors Maximum number of distinct 8-bit RGB colors to track in $distinctColors.
     *                                           Set to 0 (default) to disable tracking entirely.
     * @param array<int, int> $distinctColors    Out-parameter: 24-bit RGB histogram (key = (r<<16)|(g<<8)|b, value = occurrences)
     *                                           capped at $maxDistinctColors distinct entries.
     *                                           Already-seen colors continue to accumulate counts even once the cap is reached.
     *                                           Empty when $maxDistinctColors is 0.
     *
     * @param-out array<int, int> $histo
     * @param-out array<int, int> $distinctColors
     */
    private function loadImage(
        mixed $sourceImage,
        int $quality,
        array &$histo,
        ?ImageRegion $region = null,
        int $maxDistinctColors = 0,
        array &$distinctColors = [],
        int $whiteThreshold = 250,
        int $alphaThreshold = 125,
        float $minSaturation = 0,
    ): int {
        $loader = new ImageLoader();
        if (null !== $this->preferredAdapter) {
            $loader->setPreferredAdapter($this->preferredAdapter);
        }
        $image = $loader->load($sourceImage);
        $startX = 0;
        $startY = 0;
        $width = $image->getWidth();
        $height = $image->getHeight();

        if ($region) {
            $startX = $region->x;
            $startY = $region->y;
            $width = $region->width ?? ($width - $startX);
            $height = $region->height ?? ($height - $startY);

            if ((($startX + $width) > $image->getWidth()) || (($startY + $height) > $image->getHeight())) {
                throw new InvalidArgumentException('Region is out of image bounds.');
            }
        }

        $histo = [];
        $distinctColors = [];
        $numUsefulPixels = 0;
        $pixelCount = $width * $height;

        for ($i = 0; $i < $pixelCount; $i += $quality) {
            $x = $startX + ($i % $width);
            $y = (int) ($startY + $i / $width);
            $color = $image->getPixelColor($x, $y);

            // Skip transparent pixels
            if ($color->alpha < $alphaThreshold) {
                continue;
            }

            // Skip white pixels
            if ($color->red > $whiteThreshold && $color->green > $whiteThreshold && $color->blue > $whiteThreshold) {
                continue;
            }

            // Skip low-saturation pixels
            if ($minSaturation > 0) {
                $max = max($color->red, $color->green, $color->blue);
                if (0 === $max || ($max - min($color->red, $color->green, $color->blue)) / $max < $minSaturation) {
                    continue;
                }
            }

            // Track distinct 8-bit RGB colors up to $maxDistinctColors entries.
            // Already-seen colors keep accumulating counts even after the cap is reached.
            if ($maxDistinctColors > 0) {
                $colorKey = ($color->red << 16) | ($color->green << 8) | $color->blue;
                if (isset($distinctColors[$colorKey]) || \count($distinctColors) < $maxDistinctColors) {
                    $distinctColors[$colorKey] = ($distinctColors[$colorKey] ?? 0) + 1;
                }
            }

            // Count this pixel in its histogram bucket.
            ++$numUsefulPixels;
            [$x, $y, $z] = $color->toColorspace($this->colorSpace);
            $bucketIndex = Mmcq::getColorIndex($x, $y, $z);
            $histo[$bucketIndex] = ($histo[$bucketIndex] ?? 0) + 1;
        }

        // Don't destroy a resource passed by the user !
        // TODO Add a method in ImageLoader to know if the image should be destroy
        // (or to know the detected image source type)
        if (\is_string($sourceImage)) {
            $image->destroy();
        }

        return $numUsefulPixels;
    }
}
