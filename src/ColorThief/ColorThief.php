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

/*
 * Color Thief PHP
 *
 * Grabs the dominant color or a representative color palette from an image.
 *
 * This class requires the GD library to be installed on the server.
 *
 * It's a PHP port of the Color Thief Javascript library
 * (http://github.com/lokesh/color-thief), using the MMCQ
 * (modified median cut quantization) algorithm from
 * the Leptonica library (http://www.leptonica.com/).
 *
 * by Kevin Subileau - http://www.kevinsubileau.fr
 * Based on the work done by Lokesh Dhakar - http://www.lokeshdhakar.com
 * and Nick Rabinowitz
 *
 * Thanks
 * ------
 * Lokesh Dhakar - For creating the original project.
 * Nick Rabinowitz - For creating quantize.js.
 *
 */

namespace ColorThief;

use ColorThief\Colors\RgbColor;
use ColorThief\Exception\InvalidArgumentException;
use ColorThief\Exception\NotSupportedException;
use ColorThief\Image\Adapter\AdapterInterface;
use ColorThief\Image\ImageLoader;
use ColorThief\Internal\Mmcq;

class ColorThief
{
    public const THRESHOLD_ALPHA = 62;
    public const THRESHOLD_WHITE = 250;

    /**
     * Gets the dominant color from the image using the median cut algorithm to cluster similar colors.
     *
     * @param mixed                        $sourceImage Path to the image, GD resource, Imagick/Gmagick instance, or image as binary string
     * @param int                          $quality     1 is the highest quality. There is a trade-off between quality and speed.
     *                                                  It determines how many pixels are skipped before the next one is sampled.
     *                                                  We rarely need to sample every single pixel in the image to get good results.
     *                                                  The bigger the number, the faster the palette generation but the greater the
     *                                                  likelihood that colors will be missed.
     * @param array|null                   $area        It allows you to specify a rectangular area in the image in order to get
     *                                                  colors only for this area. It needs to be an associative array with the
     *                                                  following keys:
     *                                                  $area['x']: The x-coordinate of the top left corner of the area. Default to 0.
     *                                                  $area['y']: The y-coordinate of the top left corner of the area. Default to 0.
     *                                                  $area['w']: The width of the area. Default to image width minus x-coordinate.
     *                                                  $area['h']: The height of the area. Default to image height minus y-coordinate.
     * @param AdapterInterface|string|null $adapter     Optional argument to choose a preferred image adapter to use for loading the image.
     *                                                  By default, the adapter is automatically chosen depending on the available extensions
     *                                                  and the type of $sourceImage (for example Imagick is used if $sourceImage is an Imagick instance).
     *                                                  You can pass one of the 'Imagick', 'Gmagick' or 'Gd' string to use the corresponding
     *                                                  underlying image extension, or you can pass an instance of any class implementing
     *                                                  the AdapterInterface interface to use a custom image loader.
     *
     * @phpstan-param ?RectangularArea $area
     */
    public static function getColor(mixed $sourceImage, int $quality = 10, ?array $area = null, AdapterInterface|string|null $adapter = null): ?RgbColor
    {
        $palette = self::getPalette($sourceImage, 5, $quality, $area, $adapter);

        if ($palette->isEmpty()) {
            return null;
        }

        return $palette[0];
    }

    /**
     * Gets a palette of dominant colors from the image using the median cut algorithm to cluster similar colors.
     *
     * @param mixed                        $sourceImage Path to the image, GD resource, Imagick/Gmagick instance, or image as binary string
     * @param int                          $colorCount  it determines the size of the palette; the number of colors returned
     * @param int                          $quality     1 is the highest quality. There is a trade-off between quality and speed.
     *                                                  It determines how many pixels are skipped before the next one is sampled.
     *                                                  We rarely need to sample every single pixel in the image to get good results.
     *                                                  The bigger the number, the faster the palette generation but the greater the
     *                                                  likelihood that colors will be missed.
     * @param array|null                   $area        It allows you to specify a rectangular area in the image in order to get
     *                                                  colors only for this area. It needs to be an associative array with the
     *                                                  following keys:
     *                                                  $area['x']: The x-coordinate of the top left corner of the area. Default to 0.
     *                                                  $area['y']: The y-coordinate of the top left corner of the area. Default to 0.
     *                                                  $area['w']: The width of the area. Default to image width minus x-coordinate.
     *                                                  $area['h']: The height of the area. Default to image height minus y-coordinate.
     * @param AdapterInterface|string|null $adapter     Optional argument to choose a preferred image adapter to use for loading the image.
     *                                                  By default, the adapter is automatically chosen depending on the available extensions
     *                                                  and the type of $sourceImage (e.g. Imagick is used if $sourceImage is an Imagick instance).
     *                                                  You can pass one of the 'Imagick', 'Gmagick' or 'Gd' string to use the corresponding
     *                                                  underlying image extension, or you can pass an instance of any class implementing
     *                                                  the AdapterInterface interface to use a custom image loader.
     *
     * @phpstan-param ?RectangularArea $area
     *
     * @phpstan-return ColorPalette<RgbColor>
     */
    public static function getPalette(
        mixed $sourceImage,
        int $colorCount = 10,
        int $quality = 10,
        ?array $area = null,
        AdapterInterface|string|null $adapter = null,
    ): ColorPalette {
        if ($colorCount < 2 || $colorCount > 20) {
            throw new InvalidArgumentException('The number of palette colors must be between 2 and 20 inclusive.');
        }

        if ($quality < 1) {
            throw new InvalidArgumentException('The quality argument must be an integer greater than one.');
        }

        /** @var array<int, int> $histo */
        $histo = [];
        $numPixelsAnalyzed = self::loadImage($sourceImage, $quality, $histo, $area, $adapter);
        if (0 === $numPixelsAnalyzed) {
            throw new NotSupportedException('Unable to compute the color palette of a blank or transparent image.');
        }

        // Send histogram to quantize function which clusters values
        // using median cut algorithm
        $palette = Mmcq::quantize($numPixelsAnalyzed, $colorCount, $histo);

        return new ColorPalette(...$palette);
    }

    /**
     * @param array<int, int> $histo Histogram
     *
     * @param-out array<int, int>          $histo
     *
     * @phpstan-param ?RectangularArea $area
     */
    private static function loadImage(mixed $sourceImage, int $quality, array &$histo, ?array $area = null, AdapterInterface|string|null $adapter = null): int
    {
        $loader = new ImageLoader();
        if (null !== $adapter) {
            $loader->setPreferredAdapter($adapter);
        }
        $image = $loader->load($sourceImage);
        $startX = 0;
        $startY = 0;
        $width = $image->getWidth();
        $height = $image->getHeight();

        if ($area) {
            $startX = $area['x'] ?? 0;
            $startY = $area['y'] ?? 0;
            $width = $area['w'] ?? ($width - $startX);
            $height = $area['h'] ?? ($height - $startY);

            if ((($startX + $width) > $image->getWidth()) || (($startY + $height) > $image->getHeight())) {
                throw new InvalidArgumentException('Area is out of image bounds.');
            }
        }

        $histo = [];
        $numUsefulPixels = 0;
        $pixelCount = $width * $height;

        for ($i = 0; $i < $pixelCount; $i += $quality) {
            $x = $startX + ($i % $width);
            $y = (int) ($startY + $i / $width);
            $color = $image->getPixelColor($x, $y);

            // Pixel is too transparent. Its alpha value is larger (more transparent) than THRESHOLD_ALPHA.
            // PHP's transparency range (0-127 opaque-transparent) is reverse that of Javascript (0-255 tranparent-opaque).
            if ($color->alpha > self::THRESHOLD_ALPHA) {
                continue;
            }

            // Pixel is too white to be useful. Its RGB values all exceed THRESHOLD_WHITE
            if ($color->red > self::THRESHOLD_WHITE && $color->green > self::THRESHOLD_WHITE && $color->blue > self::THRESHOLD_WHITE) {
                continue;
            }

            // Count this pixel in its histogram bucket.
            ++$numUsefulPixels;
            $bucketIndex = Mmcq::getColorIndex($color->red, $color->green, $color->blue);
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
