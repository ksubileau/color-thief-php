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

class ColorThief
{
    const SIGBITS=5;
    const RSHIFT=3;
    const MAX_ITERATIONS=1000;
    const FRACT_BY_POPULATIONS=0.75;

    // get reduced-space color index for a pixel
    public static function getColorIndex($r, $g, $b, $sigbits = self::SIGBITS)
    {
        return ($r << (2 * $sigbits)) + ($g << $sigbits) + $b;
    }

    /* Miscellaneous functions */
    public static function naturalOrder($a, $b)
    {
        return ($a < $b) ? - 1 : (($a > $b) ? 1 : 0);
    }

    /*
     * getColor(sourceImage[, quality])
     * returns {r: num, g: num, b: num}
     *
     * Use the median cut algorithm to cluster similar colors and
     * return the base color from the largest cluster. Quality is
     * an optional argument. It needs to be an integer.
     * 1 is the highest quality settings. 10 is the default.
     * There is a trade-off between quality and speed.
     * The bigger the number, the faster a color will be returned
     * but the greater the likelihood that it will not be the
     * visually most dominant color.
     *
     */
    public static function getColor($sourceImage, $quality = 10)
    {
        $palette = ColorThief::getPalette($sourceImage, 5, $quality);

        return $palette?$palette[0]:false;
    }

    /*
     * getPalette(sourceImage[, colorCount, quality])
     * returns array[ {r: num, g: num, b: num}, {r: num, g: num, b: num}, ...]
     *
     * Use the median cut algorithm to cluster similar colors.
     *
     * colorCount determines the size of the palette; the number of colors
     * returned. If not set, it defaults to 10.
     *
     * BUGGY: Function does not always return the requested amount of colors.
     * It can be +/- 2.
     *
     * quality is an optional argument. It needs to be an integer.
     * 1 is the highest quality settings. 10 is the default.
     * There is a trade-off between quality and speed. The bigger the number,
     * the faster the palette generation but the greater the likelihood that
     * colors will be missed.
     */
    public static function getPalette($sourceImage, $colorCount = 10, $quality = 10)
    {
        // short-circuit
        if ($colorCount < 2 || $colorCount > 256) {
            // echo 'wrong number of maxcolors'."\n";
            return false;
        }
        // short-circuit
        if ($quality < 1) {
            // echo 'wrong number of maxcolors'."\n";
            return false;
        }

        $ext = strtolower(pathinfo($sourceImage, PATHINFO_EXTENSION));

            switch ($ext) {
            case 'jpg':
            case 'jpeg':
                $image = imagecreatefromjpeg ($sourceImage);
                break;
            case 'gif':
                $image = imagecreatefromgif($sourceImage);
                break;
            case 'png':
                $image = imagecreatefrompng ($sourceImage);
                break;
            default:
                return false;
            }

            if ($image === FALSE)
                return false;

        $width = imagesx($image);
        $height = imagesy($image);
        $pixelCount = $width * $height;

        // Store the RGB values in an array format suitable for quantize function
        if(class_exists("SplFixedArray"))
            // SplFixedArray is faster and more memory-efficient than normal PHP array.
            // Uses it if available.
            $pixelArray = new SplFixedArray(ceil($pixelCount/$quality));
        else
            $pixelArray = array();

        $j = 0;
        for ($i = 0; $i < $pixelCount; $i = $i + $quality) {
            $x = $i % $width;
            $y = (int) ($i / $width);
            $rgba = imagecolorat($image, $x, $y);
            $colors = imagecolorsforindex($image, $rgba);
            $alpha = $colors['alpha'];
            $rgb = self::getColorIndex($colors['red'], $colors['green'], $colors['blue'], 8);

            // If pixel is mostly opaque and not white
            if ($alpha <= 62) {
                if (! ($colors['red'] > 250 && $colors['green'] > 250 && $colors['blue'] > 250)) {
                    $pixelArray[$j++] = $rgb;
                }
            }
        }

        if(class_exists("SplFixedArray"))
            $pixelArray->setSize($j);

        imagedestroy($image);

        // Send array to quantize function which clusters values
        // using median cut algorithm
        $cmap = ColorThief::quantize($pixelArray, $colorCount);
        $palette = $cmap->palette();

        return $palette;
    }

    // histo (1-d array, giving the number of pixels in
    // each quantized region of color space), or null on error
    private static function getHisto($pixels)
    {
        $histo = array();

        foreach ($pixels as $rgb) {
            $rval = (($rgb >> 16) & 0xFF) >> self::RSHIFT;
            $gval = (($rgb >> 8) & 0xFF) >> self::RSHIFT;
            $bval = ($rgb & 0xFF) >> self::RSHIFT;
            $index = self::getColorIndex($rval, $gval, $bval);
            $histo[$index] = (isset($histo[$index]) ? $histo[$index] : 0) + 1;
        }

        return $histo;
    }

    private static function vboxFromPixels($pixels, array $histo)
    {
        $rmin = 1000000;
        $rmax = 0;
        $gmin = 1000000;
        $gmax = 0;
        $bmin = 1000000;
        $bmax = 0;

        // find min/max
        foreach ($pixels as $rgb) {
            $rval = (($rgb >> 16) & 0xFF) >> self::RSHIFT;
            $gval = (($rgb >> 8) & 0xFF) >> self::RSHIFT;
            $bval = ($rgb & 0xFF) >> self::RSHIFT;
            if ($rval < $rmin)
                $rmin = $rval;
            else if ($rval > $rmax)
                $rmax = $rval;

            if ($gval < $gmin)
                $gmin = $gval;
            else if ($gval > $gmax)
                $gmax = $gval;

            if ($bval < $bmin)
                $bmin = $bval;
            else if ($bval > $bmax)
                $bmax = $bval;
        }
        ;

        return new VBox($rmin, $rmax, $gmin, $gmax, $bmin, $bmax, $histo);
    }

    private static function doCut($color, $vbox, $partialsum, $total, $lookaheadsum)
    {
        $dim1 = $color . '1';
        $dim2 = $color . '2';
        $count2 = 0;

        for ($i = $vbox->$dim1; $i <= $vbox->$dim2; $i++) {
            if ($partialsum[$i] > $total / 2) {
                $vbox1 = $vbox->copy();
                $vbox2 = $vbox->copy();
                $left = $i - $vbox->$dim1;
                $right = $vbox->$dim2 - $i;

                if ($left <= $right)
                    $d2 = min($vbox->$dim2 - 1, ~ ~ ($i + $right / 2));
                else
                    $d2 = max($vbox->$dim1, ~ ~ ($i - 1 - $left / 2));
                // avoid 0-count boxes

                while (empty($partialsum[$d2]))
                    $d2 ++;

                $count2 = $lookaheadsum[$d2];
                while (! $count2 && !empty($partialsum[$d2 - 1]))
                    $count2 = $lookaheadsum[--$d2];
                // set dimensions

                $vbox1->$dim2 = $d2;
                $vbox2->$dim1 = $vbox1->$dim2 + 1;

                // echo 'vbox counts: '.$vbox->count().' '.$vbox1->count().' '.$vbox2->count()."\n";
                return array($vbox1, $vbox2);
            }
        }
    }

    private static function medianCutApply($histo, $vbox)
    {
        if (!$vbox->count())
            return;

        $rw = $vbox->r2 - $vbox->r1 + 1;
        $gw = $vbox->g2 - $vbox->g1 + 1;
        $bw = $vbox->b2 - $vbox->b1 + 1;
        $maxw = max($rw, $gw, $bw);

        // only one pixel, no split
        if ($vbox->count() == 1) {
            return array ($vbox->copy());
        }

        /* Find the partial sum arrays along the selected axis. */
        $total = 0;
        $partialsum = array ();
        $lookaheadsum = array ();

        if ($maxw == $rw) {
            for ($i = $vbox->r1; $i <= $vbox->r2; $i++) {
                $sum = 0;
                for ($j = $vbox->g1; $j <= $vbox->g2; $j++) {
                    for ($k = $vbox->b1; $k <= $vbox->b2; $k++) {
                        $index = self::getColorIndex($i, $j, $k);
                        if (isset($histo[$index]))
                            $sum += $histo[$index];
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
                        if (isset($histo[$index]))
                            $sum += $histo[$index];
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
                        if (isset($histo [$index]))
                            $sum += $histo[$index];
                    }
                }
                $total += $sum;
                $partialsum[$i] = $total;
            }
        }

        foreach ($partialsum as $i => $d) {
            $lookaheadsum[$i] = $total - $d;
        }

        // determine the cut planes
        if ($maxw == $rw)
            return ColorThief::doCut('r', $vbox, $partialsum, $total, $lookaheadsum);
        else if ($maxw == $gw)
            return ColorThief::doCut('g', $vbox, $partialsum, $total, $lookaheadsum);
        else
            return ColorThief::doCut('b', $vbox, $partialsum, $total, $lookaheadsum);
    }


    // inner function to do the iteration
    private static function quantize_iter(&$lh, $target, $histo)
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
            $vboxes = ColorThief::medianCutApply($histo, $vbox);
            $vbox1 = $vboxes[0];
            $vbox2 = $vboxes[1];

            if (! $vbox1) {
                // echo "vbox1 not defined; shouldn't happen!"."\n";
                return;
            }

            $lh->push($vbox1);
            if ($vbox2) { /* vbox2 can be null */
                $lh->push($vbox2);
                $ncolors++;
            }
            if ($ncolors >= $target)
                return;
            if ($niters++ > self::MAX_ITERATIONS) {
                // echo "infinite loop; perhaps too few pixels!"."\n";
                return;
            }
        }
    }

    private static function quantize($pixels, $maxcolors)
    {
        // short-circuit
        if (! count($pixels) || $maxcolors < 2 || $maxcolors > 256) {
            // echo 'wrong number of maxcolors'."\n";
            return false;
        }

        $histo = ColorThief::getHisto($pixels);

        // check that we aren't below maxcolors already
        if (count($histo) <= $maxcolors) {
            // XXX: generate the new colors from the histo and return
        }

        $vbox = ColorThief::vboxFromPixels($pixels, $histo);

        $pq = new PQueue(function ($a, $b) {
            return self::naturalOrder($a->count(), $b->count());
        });
        $pq->push($vbox);

        // first set of colors, sorted by population
        ColorThief::quantize_iter($pq, self::FRACT_BY_POPULATIONS * $maxcolors, $histo);

        // Re-sort by the product of pixel occupancy times the size in color space.
        $pq2 = new PQueue(function ($a, $b) {
            return self::naturalOrder($a->count () * $a->volume (), $b->count () * $b->volume ());
        });

        for ($i = $pq->size(); $i > 0; $i--) {
            $pq2->push($pq->pop());
        }

        // next set - generate the median cuts using the (npix * vol) sorting.
        ColorThief::quantize_iter($pq2, $maxcolors - $pq2->size(), $histo);

        // calculate the actual colors
        $cmap = new CMap();

        for ($i = $pq2->size(); $i > 0; $i--) {
            $cmap->push($pq2->pop());
        }

        return $cmap;
    }
}
