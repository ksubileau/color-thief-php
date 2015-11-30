<?php

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
 * License
 * -------
 * Creative Commons Attribution 2.5 License:
 * http://creativecommons.org/licenses/by/2.5/
 *
 * Thanks
 * ------
 * Lokesh Dhakar - For creating the original project.
 * Nick Rabinowitz - For creating quantize.js.
 *
 */

namespace ColorThief;

use SplFixedArray;
use ColorThief\Image\ImageLoader;

class ColorThief
{
    const SIGBITS=5;
    const RSHIFT=3;
    const MAX_ITERATIONS=1000;
    const FRACT_BY_POPULATIONS=0.75;

    /**
     * Get reduced-space color index for a pixel
     *
     * @param int $red
     * @param int $green
     * @param int $blue
     * @param int $sigBits
     * @return int
     */
    public static function getColorIndex($red, $green, $blue, $sigBits = self::SIGBITS)
    {
        return ($red << (2 * $sigBits)) + ($green << $sigBits) + $blue;
    }

    /**
     * Get red, green and blue components from reduced-space color index for a pixel
     *
     * @param int $index
     * @param int $rShift
     * @param int $sigBits
     * @return array
     */
    public static function getColorsFromIndex($index, $rShift = self::RSHIFT, $sigBits = 8)
    {
        $mask = (1 << $sigBits) - 1;
        $red = (($index >> (2 * $sigBits)) & $mask) >> $rShift;
        $green = (($index >> $sigBits) & $mask) >> $rShift;
        $blue = ($index & $mask) >> $rShift;
        return array($red, $green, $blue);
    }


    /**
     * Natural sorting
     *
     * @param int $a
     * @param int $b
     * @return int
     */
    public static function naturalOrder($a, $b)
    {
        return ($a < $b) ? - 1 : (($a > $b) ? 1 : 0);
    }

    /**
     * Use the median cut algorithm to cluster similar colors.
     *
     * @bug Function does not always return the requested amount of colors. It can be +/- 2.
     *
     * @param string     $sourceImage   path or url to the image
     * @param int        $quality       1 is the highest quality. There is a trade-off between quality and speed.
     *                                  The bigger the number, the faster the palette generation but the greater the
     *                                  likelihood that colors will be missed.
     * @param array|null $area[x,y,w,h] It allows you to specify a rectangular area in the image in order to get
     *                                  colors only for this area. It needs to be an associative array with the
     *                                  following keys:
     *                                  $area['x']: The x-coordinate of the top left corner of the area. Default to 0.
     *                                  $area['y']: The y-coordinate of the top left corner of the area. Default to 0.
     *                                  $area['w']: The width of the area. Default to the width of the image minus x-coordinate.
     *                                  $area['h']: The height of the area. Default to the height of the image minus y-coordinate.
     *
     * @return array|bool
     */
    public static function getColor($sourceImage, $quality = 10, array $area = null)
    {
        $palette = static::getPalette($sourceImage, 5, $quality, $area);

        return $palette ? $palette[0] : false;
    }

    /**
     * Use the median cut algorithm to cluster similar colors.
     *
     * @bug Function does not always return the requested amount of colors. It can be +/- 2.
     *
     * @param string     $sourceImage   path or url to the image
     * @param int        $colorCount    It determines the size of the palette; the number of colors returned.
     * @param int        $quality       1 is the highest quality.
     * @param array|null $area[x,y,w,h]
     *
     * @return array
     */
    public static function getPalette($sourceImage, $colorCount = 10, $quality = 10, array $area = null)
    {
        // short-circuit
        if ($colorCount < 2 || $colorCount > 256) {
            throw new \InvalidArgumentException("The number of palette colors must be between 2 and 256 inclusive.");
        }
        // short-circuit
        if ($quality < 1) {
            throw new \InvalidArgumentException("The quality argument must be an integer greater than one.");
        }

        $pixelArray = static::loadImage($sourceImage, $quality, $area);
        if (!count($pixelArray)) {
            throw new \RuntimeException("Unable to compute the color palette of a blank or transparent image.", 1);
        }

        // Send array to quantize function which clusters values
        // using median cut algorithm
        $cmap = static::quantize($pixelArray, $colorCount);
        $palette = $cmap->palette();

        return $palette;
    }


    /**
     * Histo: 1-d array, giving the number of pixels in each quantized region of color space
     *
     * @param array $pixels
     * @return array
     */
    private static function getHisto($pixels)
    {
        $histo = array();

        foreach ($pixels as $rgb) {
            list($red, $green, $blue) = static::getColorsFromIndex($rgb);
            $index = self::getColorIndex($red, $green, $blue);
            $histo[$index] = (isset($histo[$index]) ? $histo[$index] : 0) + 1;
        }

        return $histo;
    }

    /**
     * @param string $sourceImage path or http
     * @param int $quality
     * @param array|null $area
     * @return SplFixedArray
     */
    private static function loadImage($sourceImage, $quality, array $area = null)
    {
        $loader = new ImageLoader();
        $image  = $loader->load($sourceImage);
        $startX = 0;
        $startY = 0;
        $width  = $image->getWidth();
        $height = $image->getHeight();

        if ($area) {
            $startX = isset($area['x']) ? $area['x'] : 0;
            $startY = isset($area['y']) ? $area['y'] : 0;
            $width  = isset($area['w']) ? $area['w'] : ($width  - $startX);
            $height = isset($area['h']) ? $area['h'] : ($height - $startY);

            if ((($startX + $width) > $image->getWidth()) || (($startY + $height) > $image->getHeight())) {
                throw new \InvalidArgumentException("Area is out of image bounds.");
            }
        }

        $pixelCount = $width * $height;

        // Store the RGB values in an array format suitable for quantize function
        // SplFixedArray is faster and more memory-efficient than normal PHP array.
        $pixelArray = new SplFixedArray(ceil($pixelCount/$quality));

        $size = 0;
        for ($i = 0; $i < $pixelCount; $i = $i + $quality) {
            $x = $startX + ($i % $width);
            $y = (int) ($startY + $i / $width);
            $color = $image->getPixelColor($x, $y);

            // If pixel is mostly opaque and not white
            if (self::isClearlyVisible($color) && self::isNonWhite($color)) {
                $pixelArray[$size++] = self::getColorIndex($color->red, $color->green, $color->blue, 8);
                // TODO : Compute directly the histogram here ? (save one iteration over all pixels)
            }
        }

        $pixelArray->setSize($size);

        // Don't destroy a resource passed by the user !
        if (is_string($sourceImage)) {
            $image->destroy();
        }

        return $pixelArray;
    }

    /**
     * @param object $color
     * @return bool
     */
    protected static function isClearlyVisible($color)
    {
        return $color->alpha <= 62;
    }

    /**
     * @param object $color
     * @return bool
     */
    protected static function isNonWhite($color)
    {
        return $color->red <= 250 && $color->green <= 250 && $color->blue <= 250;
    }

    /**
     * @param array $histo
     * @return VBox
     */
    private static function vboxFromHistogram(array $histo)
    {
        $rmin = PHP_INT_MAX;
        $rmax = 0;
        $gmin = PHP_INT_MAX;
        $gmax = 0;
        $bmin = PHP_INT_MAX;
        $bmax = 0;

        // find min/max
        foreach ($histo as $index => $count) {
            list($rval, $gval, $bval) = static::getColorsFromIndex($index, 0, ColorThief::SIGBITS);

            if ($rval < $rmin) {
                $rmin = $rval;
            } elseif ($rval > $rmax) {
                $rmax = $rval;
            }

            if ($gval < $gmin) {
                $gmin = $gval;
            } elseif ($gval > $gmax) {
                $gmax = $gval;
            }

            if ($bval < $bmin) {
                $bmin = $bval;
            } elseif ($bval > $bmax) {
                $bmax = $bval;
            }
        }

        return new VBox($rmin, $rmax, $gmin, $gmax, $bmin, $bmax, $histo);
    }

    /**
     * @param $color
     * @param $vbox
     * @param $partialSum
     * @param $total
     *
     * @return array
     */
    private static function doCut($color, $vbox, $partialSum, $total)
    {
        $dim1 = $color . '1';
        $dim2 = $color . '2';

        for ($i = $vbox->$dim1; $i <= $vbox->$dim2; $i++) {
            if ($partialSum[$i] > $total / 2) {
                $vbox1 = $vbox->copy();
                $vbox2 = $vbox->copy();
                $left = $i - $vbox->$dim1;
                $right = $vbox->$dim2 - $i;

                // Choose the cut plane within the greater of the (left, right) sides
                // of the bin in which the median pixel resides
                if ($left <= $right) {
                    $d2 = min($vbox->$dim2 - 1, ~ ~ ($i + $right / 2));
                } else { /* left > right */
                    $d2 = max($vbox->$dim1, ~ ~ ($i - 1 - $left / 2));
                }

                while (empty($partialSum[$d2])) {
                    $d2 ++;
                }
                // Avoid 0-count boxes
                while ($partialSum[$d2] >= $total  && !empty($partialSum[$d2 - 1])) {
                    --$d2;
                }

                // set dimensions
                $vbox1->$dim2 = $d2;
                $vbox2->$dim1 = $d2 + 1;

                // echo 'vbox counts: '.$vbox->count().' '.$vbox1->count().' '.$vbox2->count()."\n";
                return array($vbox1, $vbox2);
            }
        }
    }

    /**
     * @param array $histo
     * @param $vbox
     * @return array|void
     */
    private static function medianCutApply($histo, $vbox)
    {
        if (!$vbox->count()) {
            return;
        }

        // If the vbox occupies just one element in color space, it can't be split
        if ($vbox->count() == 1) {
            return array ($vbox->copy());
        }

        // Select the longest axis for splitting
        $rw = $vbox->r2 - $vbox->r1 + 1;
        $gw = $vbox->g2 - $vbox->g1 + 1;
        $bw = $vbox->b2 - $vbox->b1 + 1;
        $maxw = max($rw, $gw, $bw);

        /* Find the partial sum arrays along the selected axis. */
        $total = 0;
        $partialsum = array();

        if ($maxw == $rw) {
            for ($i = $vbox->r1; $i <= $vbox->r2; $i++) {
                $sum = 0;
                for ($j = $vbox->g1; $j <= $vbox->g2; $j++) {
                    for ($k = $vbox->b1; $k <= $vbox->b2; $k++) {
                        $index = self::getColorIndex($i, $j, $k);
                        if (isset($histo[$index])) {
                            $sum += $histo[$index];
                        }
                    }
                }
                $total += $sum;
                $partialsum[$i] = $total;
            }
        } elseif ($maxw == $gw) {
            for ($i = $vbox->g1; $i <= $vbox->g2; $i++) {
                $sum = 0;
                for ($j = $vbox->r1; $j <= $vbox->r2; $j++) {
                    for ($k = $vbox->b1; $k <= $vbox->b2; $k++) {
                        $index = self::getColorIndex($j, $i, $k);
                        if (isset($histo[$index])) {
                            $sum += $histo[$index];
                        }
                    }
                }
                $total += $sum;
                $partialsum[$i] = $total;
            }
        } else { /* maxw == bw */
            for ($i = $vbox->b1; $i <= $vbox->b2; $i++) {
                $sum = 0;
                for ($j = $vbox->r1; $j <= $vbox->r2; $j++) {
                    for ($k = $vbox->g1; $k <= $vbox->g2; $k++) {
                        $index = self::getColorIndex($j, $k, $i);
                        if (isset($histo [$index])) {
                            $sum += $histo[$index];
                        }
                    }
                }
                $total += $sum;
                $partialsum[$i] = $total;
            }
        }

        // Determine the cut planes
        if ($maxw == $rw) {
            return static::doCut('r', $vbox, $partialsum, $total);
        } elseif ($maxw == $gw) {
            return static::doCut('g', $vbox, $partialsum, $total);
        } else {
            return static::doCut('b', $vbox, $partialsum, $total);
        }
    }



    /**
     * Inner function to do the iteration
     *
     * @param PQueue $lh
     * @param float $target
     * @param array $histo
     */
    private static function quantizeIter(&$lh, $target, $histo)
    {
        $ncolors = 1;
        $niters = 0;

        while ($niters < self::MAX_ITERATIONS) {
            $vbox = $lh->pop();

            if (! $vbox->count()) { /* just put it back */
                $lh->push($vbox);
                $niters++;
                continue;
            }
            // do the cut
            $vboxes = static::medianCutApply($histo, $vbox);

            if (! (is_array($vboxes) && isset($vboxes[0]))) {
                // echo "vbox1 not defined; shouldn't happen!"."\n";
                return;
            }

            $lh->push($vboxes[0]);

            if (isset($vboxes[1])) { /* vbox2 can be null */
                $lh->push($vboxes[1]);
                $ncolors++;
            }

            if ($ncolors >= $target) {
                return;
            }

            if ($niters++ > self::MAX_ITERATIONS) {
                // echo "infinite loop; perhaps too few pixels!"."\n";
                return;
            }
        }
    }

    /**
     * @param SplFixedArray $pixels
     * @param $maxColors
     * @return bool|CMap
     */
    private static function quantize($pixels, $maxColors)
    {
        // short-circuit
        if (! count($pixels) || $maxColors < 2 || $maxColors > 256) {
            // echo 'wrong number of maxcolors'."\n";
            return false;
        }

        $histo = static::getHisto($pixels);

        // check that we aren't below maxcolors already
        //if (count($histo) <= $maxcolors) {
            // XXX: generate the new colors from the histo and return
        //}

        $vbox = static::vboxFromHistogram($histo);

        $pq = new PQueue(function ($a, $b) {
            return ColorThief::naturalOrder($a->count(), $b->count());
        });
        $pq->push($vbox);

        // first set of colors, sorted by population
        static::quantizeIter($pq, self::FRACT_BY_POPULATIONS * $maxColors, $histo);

        // Re-sort by the product of pixel occupancy times the size in color space.
        $pq->setComparator(function ($a, $b) {
            return ColorThief::naturalOrder($a->count() * $a->volume(), $b->count() * $b->volume());
        });

        // next set - generate the median cuts using the (npix * vol) sorting.
        static::quantizeIter($pq, $maxColors - $pq->size(), $histo);

        // calculate the actual colors
        $cmap = new CMap();

        for ($i = $pq->size(); $i > 0; $i--) {
            $cmap->push($pq->pop());
        }

        return $cmap;
    }
}
