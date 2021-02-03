<?php

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

use ColorThief\Image\ImageLoader;
use SplFixedArray;

class ColorThief
{
    public const SIGBITS = 5;
    public const RSHIFT = 3;
    public const MAX_ITERATIONS = 1000;
    public const FRACT_BY_POPULATIONS = 0.75;
    public const THRESHOLD_ALPHA = 62;
    public const THRESHOLD_WHITE = 250;

    /**
     * Get combined color index (3 colors as one integer) from RGB values (0-255) or RGB Histogram Buckets (0-31).
     */
    public static function getColorIndex(int $red, int $green, int $blue, int $sigBits = self::SIGBITS): int
    {
        return (($red >> (8 - $sigBits)) << (2 * $sigBits)) | (($green >> (8 - $sigBits)) << $sigBits) | ($blue >> (8 - $sigBits));
    }

    /**
     * Get RGB values (0-255) or RGB Histogram Buckets from a combined color index (3 colors as one integer).
     *
     * @phpstan-return ColorRGB
     */
    public static function getColorsFromIndex(int $index, int $sigBits = 8): array
    {
        $mask = (1 << $sigBits) - 1;

        $red = ($index >> (2 * $sigBits)) & $mask;
        $green = ($index >> $sigBits) & $mask;
        $blue = $index & $mask;

        return [$red, $green, $blue];
    }

    /**
     * Use the median cut algorithm to cluster similar colors.
     *
     * @bug Function does not always return the requested amount of colors. It can be +/- 2.
     *
     * @param mixed      $sourceImage Path/URL to the image, GD resource, Imagick instance, or image as binary string
     * @param int        $quality     1 is the highest quality. There is a trade-off between quality and speed.
     *                                The bigger the number, the faster the palette generation but the greater the
     *                                likelihood that colors will be missed.
     * @param array|null $area        It allows you to specify a rectangular area in the image in order to get
     *                                colors only for this area. It needs to be an associative array with the
     *                                following keys:
     *                                $area['x']: The x-coordinate of the top left corner of the area. Default to 0.
     *                                $area['y']: The y-coordinate of the top left corner of the area. Default to 0.
     *                                $area['w']: The width of the area. Default to image width minus x-coordinate.
     *                                $area['h']: The height of the area. Default to image height minus y-coordinate.
     * @phpstan-param ?RectangularArea $area
     *
     * @phpstan-return ColorRGB|null
     */
    public static function getColor($sourceImage, int $quality = 10, ?array $area = null): ?array
    {
        $palette = static::getPalette($sourceImage, 5, $quality, $area);

        return $palette ? $palette[0] : null;
    }

    /**
     * Use the median cut algorithm to cluster similar colors.
     *
     * @bug Function does not always return the requested amount of colors. It can be +/- 2.
     *
     * @param mixed      $sourceImage Path/URL to the image, GD resource, Imagick instance, or image as binary string
     * @param int        $colorCount  it determines the size of the palette; the number of colors returned
     * @param int        $quality     1 is the highest quality
     * @param array|null $area        [x,y,w,h]
     * @phpstan-param ?RectangularArea $area
     *
     * @return array
     * @phpstan-return ColorRGB[]
     */
    public static function getPalette(
        $sourceImage,
        int $colorCount = 10,
        int $quality = 10,
        ?array $area = null
    ): ?array {
        if ($colorCount < 2 || $colorCount > 256) {
            throw new \InvalidArgumentException('The number of palette colors must be between 2 and 256 inclusive.');
        }

        if ($quality < 1) {
            throw new \InvalidArgumentException('The quality argument must be an integer greater than one.');
        }

        $histo = [];
        $numPixelsAnalyzed = static::loadImage($sourceImage, $quality, $histo, $area);
        if (0 === $numPixelsAnalyzed) {
            throw new \RuntimeException('Unable to compute the color palette of a blank or transparent image.', 1);
        }

        // Send histogram to quantize function which clusters values
        // using median cut algorithm
        $cmap = static::quantize($numPixelsAnalyzed, $colorCount, $histo);
        $palette = $cmap->palette();

        return $palette;
    }

    /**
     * @param mixed           $sourceImage Path/URL to the image, GD resource, Imagick instance, or image as binary string
     * @param int             $quality     Analyze every $quality pixels
     * @param array<int, int> $histo       Histogram
     * @phpstan-param ?RectangularArea $area
     */
    private static function loadImage($sourceImage, int $quality, array &$histo, array $area = null): int
    {
        $loader = new ImageLoader();
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
                throw new \InvalidArgumentException('Area is out of image bounds.');
            }
        }

        // Fill a SplArray with zeroes to initialize the 5-bit buckets and avoid having to check isset in the pixel loop.
        // There are 32768 buckets because each color is 5 bits (15 bits total for RGB values).
        $totalBuckets = (1 << (3 * self::SIGBITS));
        $histoSpl = new SplFixedArray($totalBuckets);
        for ($i = 0; $i < $totalBuckets; ++$i) {
            $histoSpl[$i] = 0;
        }

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
            $bucketIndex = static::getColorIndex($color->red, $color->green, $color->blue);
            $histoSpl[$bucketIndex] = $histoSpl[$bucketIndex] + 1;
        }

        // Copy the histogram buckets that had pixels back to a normal array.
        $histo = [];
        foreach ($histoSpl as $bucketInt => $numPixels) {
            if ($numPixels > 0) {
                $histo[$bucketInt] = $numPixels;
            }
        }

        // Don't destroy a resource passed by the user !
        // TODO Add a method in ImageLoader to know if the image should be destroy
        // (or to know the detected image source type)
        if (\is_string($sourceImage)) {
            $image->destroy();
        }

        return $numUsefulPixels;
    }

    /**
     * @param array<int, int> $histo
     */
    private static function vboxFromHistogram(array $histo): VBox
    {
        $rgbMin = [\PHP_INT_MAX, \PHP_INT_MAX, \PHP_INT_MAX];
        $rgbMax = [-\PHP_INT_MAX, -\PHP_INT_MAX, -\PHP_INT_MAX];

        // find min/max
        foreach ($histo as $bucketIndex => $count) {
            $rgb = static::getColorsFromIndex($bucketIndex, self::SIGBITS);

            // For each color components
            for ($i = 0; $i < 3; ++$i) {
                if ($rgb[$i] < $rgbMin[$i]) {
                    $rgbMin[$i] = $rgb[$i];
                }
                if ($rgb[$i] > $rgbMax[$i]) {
                    $rgbMax[$i] = $rgb[$i];
                }
            }
        }

        return new VBox($rgbMin[0], $rgbMax[0], $rgbMin[1], $rgbMax[1], $rgbMin[2], $rgbMax[2], $histo);
    }

    /**
     * @param int[] $partialSum
     *
     * @return array{VBox, VBox}|null
     */
    private static function doCut(string $color, VBox $vBox, array $partialSum, int $total): ?array
    {
        $dim1 = $color.'1';
        $dim2 = $color.'2';

        for ($i = $vBox->$dim1; $i <= $vBox->$dim2; ++$i) {
            if ($partialSum[$i] > $total / 2) {
                $vBox1 = $vBox->copy();
                $vBox2 = $vBox->copy();
                $left = $i - $vBox->$dim1;
                $right = $vBox->$dim2 - $i;

                // Choose the cut plane within the greater of the (left, right) sides
                // of the bin in which the median pixel resides
                if ($left <= $right) {
                    $d2 = min($vBox->$dim2 - 1, (int) ($i + $right / 2));
                } else { /* left > right */
                    $d2 = max($vBox->$dim1, (int) ($i - 1 - $left / 2));
                }

                while (empty($partialSum[$d2])) {
                    ++$d2;
                }
                // Avoid 0-count boxes
                while ($partialSum[$d2] >= $total && !empty($partialSum[$d2 - 1])) {
                    --$d2;
                }

                // set dimensions
                $vBox1->$dim2 = $d2;
                $vBox2->$dim1 = $d2 + 1;

                return [$vBox1, $vBox2];
            }
        }

        return null;
    }

    /**
     * @param array<int, int> $histo
     *
     * @return VBox[]|null
     */
    private static function medianCutApply(array $histo, VBox $vBox): ?array
    {
        if (!$vBox->count()) {
            return null;
        }

        // If the vbox occupies just one element in color space, it can't be split
        if (1 == $vBox->count()) {
            return [
                $vBox->copy(),
            ];
        }

        // Select the longest axis for splitting
        $cutColor = $vBox->longestAxis();

        // Find the partial sum arrays along the selected axis.
        [$total, $partialSum] = static::sumColors($cutColor, $histo, $vBox);

        return static::doCut($cutColor, $vBox, $partialSum, $total);
    }

    /**
     * Find the partial sum arrays along the selected axis.
     *
     * @param string $axis r|g|b
     * @phpstan-param 'r'|'g'|'b' $axis
     *
     * @param array<int, int> $histo
     *
     * @return array{int, array<int, int>} [$total, $partialSum]
     */
    private static function sumColors(string $axis, array $histo, VBox $vBox): array
    {
        $total = 0;
        $partialSum = [];

        // The selected axis should be the first range
        $colorIterateOrder = array_diff(['r', 'g', 'b'], [$axis]);
        array_unshift($colorIterateOrder, $axis);

        // Retrieves iteration ranges
        [$firstRange, $secondRange, $thirdRange] = static::getVBoxColorRanges($vBox, $colorIterateOrder);

        foreach ($firstRange as $firstColor) {
            $sum = 0;
            foreach ($secondRange as $secondColor) {
                foreach ($thirdRange as $thirdColor) {
                    // Rearrange color components
                    $bucket = [
                        $colorIterateOrder[0] => $firstColor,
                        $colorIterateOrder[1] => $secondColor,
                        $colorIterateOrder[2] => $thirdColor,
                    ];

                    // The getColorIndex function takes RGB values instead of buckets. The left shift converts our bucket into its RGB value.
                    $bucketIndex = static::getColorIndex(
                        $bucket['r'] << self::RSHIFT,
                        $bucket['g'] << self::RSHIFT,
                        $bucket['b'] << self::RSHIFT,
                        self::SIGBITS
                    );

                    if (isset($histo[$bucketIndex])) {
                        $sum += $histo[$bucketIndex];
                    }
                }
            }
            $total += $sum;
            $partialSum[$firstColor] = $total;
        }

        return [$total, $partialSum];
    }

    /**
     * @phpstan-param array<'r'|'g'|'b'> $order
     *
     * @return int[][]
     * @phpstan-return array{int[], int[], int[]}
     */
    private static function getVBoxColorRanges(VBox $vBox, array $order): array
    {
        $ranges = [
            'r' => range($vBox->r1, $vBox->r2),
            'g' => range($vBox->g1, $vBox->g2),
            'b' => range($vBox->b1, $vBox->b2),
        ];

        return [
            $ranges[$order[0]],
            $ranges[$order[1]],
            $ranges[$order[2]],
        ];
    }

    /**
     * Inner function to do the iteration.
     *
     * @param PQueue<VBox>    $priorityQueue
     * @param array<int, int> $histo
     */
    private static function quantizeIter(PQueue &$priorityQueue, float $target, array $histo): void
    {
        $nColors = 1;
        $nIterations = 0;

        while ($nIterations < static::MAX_ITERATIONS) {
            $vBox = $priorityQueue->pop();

            if (!$vBox->count()) { /* just put it back */
                $priorityQueue->push($vBox);
                ++$nIterations;
                continue;
            }
            // do the cut
            $vBoxes = static::medianCutApply($histo, $vBox);

            if (!(\is_array($vBoxes) && isset($vBoxes[0]))) {
                // echo "vbox1 not defined; shouldn't happen!"."\n";
                return;
            }

            $priorityQueue->push($vBoxes[0]);

            if (isset($vBoxes[1])) { /* vbox2 can be null */
                $priorityQueue->push($vBoxes[1]);
                ++$nColors;
            }

            if ($nColors >= $target) {
                return;
            }

            if ($nIterations++ > static::MAX_ITERATIONS) {
                // echo "infinite loop; perhaps too few pixels!"."\n";
                return;
            }
        }
    }

    /**
     * @param int             $numPixels Number of image pixels analyzed
     * @param array<int, int> $histo     Histogram
     */
    private static function quantize(int $numPixels, int $maxColors, array &$histo): CMap
    {
        // Short-Circuits
        if (0 === $numPixels) {
            throw new \InvalidArgumentException('Zero useable pixels found in image.');
        }
        if ($maxColors < 2 || $maxColors > 256) {
            throw new \InvalidArgumentException('The maxColors parameter must be between 2 and 256 inclusive.');
        }
        if (0 === \count($histo)) {
            throw new \InvalidArgumentException('Image produced an empty histogram.');
        }

        // check that we aren't below maxcolors already
        //if (count($histo) <= $maxcolors) {
        // XXX: generate the new colors from the histo and return
        //}

        $vBox = static::vboxFromHistogram($histo);

        /** @var PQueue<VBox> $priorityQueue */
        $priorityQueue = new PQueue(function (VBox $a, VBox $b) {
            return $a->count() <=> $b->count();
        });
        $priorityQueue->push($vBox);

        // first set of colors, sorted by population
        static::quantizeIter($priorityQueue, static::FRACT_BY_POPULATIONS * $maxColors, $histo);

        // Re-sort by the product of pixel occupancy times the size in color space.
        $priorityQueue->setComparator(function (VBox $a, VBox $b) {
            return ($a->count() * $a->volume()) <=> ($b->count() * $b->volume());
        });

        // next set - generate the median cuts using the (npix * vol) sorting.
        static::quantizeIter($priorityQueue, $maxColors - $priorityQueue->size(), $histo);

        // calculate the actual colors
        $cmap = new CMap();

        for ($i = $priorityQueue->size(); $i > 0; --$i) {
            $cmap->push($priorityQueue->pop());
        }

        return $cmap;
    }
}
