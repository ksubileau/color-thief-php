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

namespace ColorThief\Internal;

use ColorThief\Color;
use ColorThief\Exception\InvalidArgumentException;
use ColorThief\Exception\RuntimeException;

/**
 * @internal
 */
final class Mmcq
{
    public const SIGBITS = 5;
    public const RSHIFT = 3;
    public const MAX_ITERATIONS = 1000;
    public const FRACT_BY_POPULATIONS = 0.75;

    /**
     * Get combined color index (3 channels as one integer) from values in [0..255] or [0..31] depending on $sigBits.
     */
    public static function getColorIndex(int $dim0, int $dim1, int $dim2, int $sigBits = self::SIGBITS): int
    {
        return (($dim0 >> (8 - $sigBits)) << (2 * $sigBits)) | (($dim1 >> (8 - $sigBits)) << $sigBits) | ($dim2 >> (8 - $sigBits));
    }

    /**
     * Get 3-channel values from a combined color index.
     *
     * @phpstan-return array{int, int, int}
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
     * @param int             $numPixels Number of image pixels analyzed
     * @param array<int, int> $histo     Histogram
     *
     * @return Color[]
     */
    public static function quantize(int $numPixels, int $maxColors, array &$histo): array
    {
        // Short-Circuits
        if (0 === $numPixels) {
            throw new InvalidArgumentException('Zero usable pixels found in image.');
        }
        if ($maxColors < 2 || $maxColors > 256) {
            throw new InvalidArgumentException('The maxColors parameter must be between 2 and 256 inclusive.');
        }
        if (0 === \count($histo)) {
            throw new InvalidArgumentException('Image produced an empty histogram.');
        }

        // check that we aren't below maxcolors already
        // if (count($histo) <= $maxcolors) {
        // XXX: generate the new colors from the histo and return
        // }

        $vBox = self::vboxFromHistogram($histo);

        /** @var PQueue<VBox> $priorityQueue */
        $priorityQueue = new PQueue(static fn (VBox $a, VBox $b): int => $a->count() <=> $b->count());
        $priorityQueue->push($vBox);

        // first set of colors, sorted by population
        self::quantizeIter($priorityQueue, self::FRACT_BY_POPULATIONS * $maxColors, $histo);

        // Re-sort by the product of pixel occupancy times the size in color space.
        $priorityQueue->setComparator(static fn (VBox $a, VBox $b): int => ($a->count() * $a->volume()) <=> ($b->count() * $b->volume()));

        // next set - generate the median cuts using the (npix * vol) sorting.
        self::quantizeIter($priorityQueue, $maxColors, $histo);

        // calculate the actual colors
        $totalPopulation = $priorityQueue->reduce(static fn (int $carry, VBox $vbox): int => $carry + $vbox->count(), 0);
        $colors = $priorityQueue->map(static function (VBox $vbox) use ($totalPopulation): Color {
            $avg = $vbox->avg();

            return new Color(
                red: $avg[0],
                green: $avg[1],
                blue: $avg[2],
                population: $vbox->count(),
                proportion: $totalPopulation > 0 ? $vbox->count() / $totalPopulation : 0,
            );
        });

        return array_reverse($colors);
    }

    /**
     * @param array<int, int> $histo
     */
    public static function vboxFromHistogram(array $histo): VBox
    {
        $rgbMin = [\PHP_INT_MAX, \PHP_INT_MAX, \PHP_INT_MAX];
        $rgbMax = [-\PHP_INT_MAX, -\PHP_INT_MAX, -\PHP_INT_MAX];

        // find min/max
        foreach (array_keys($histo) as $bucketIndex) {
            $rgb = self::getColorsFromIndex($bucketIndex, self::SIGBITS);

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
    public static function doCut(string $color, VBox $vBox, array $partialSum, int $total): ?array
    {
        $dim1 = $color.'1';
        $dim2 = $color.'2';

        /** @var int $lo */
        $lo = $vBox->$dim1;
        /** @var int $hi */
        $hi = $vBox->$dim2;

        for ($i = $lo; $i <= $hi; ++$i) {
            if ($partialSum[$i] > $total / 2) {
                $vBox1 = $vBox->copy();
                $vBox2 = $vBox->copy();
                $left = $i - $lo;
                $right = $hi - $i;

                // Choose the cut plane within the greater of the (left, right) sides
                // of the bin in which the median pixel resides
                if ($left <= $right) {
                    $d2 = min($hi - 1, (int) ($i + $right / 2));
                } else { /* left > right */
                    $d2 = max($lo, (int) ($i - 1 - $left / 2));
                }

                while (empty($partialSum[$d2])) {
                    ++$d2;
                }
                // Avoid 0-count boxes
                while ($partialSum[$d2] >= $total && (isset($partialSum[$d2 - 1]) && 0 !== $partialSum[$d2 - 1])) {
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
        if (0 === $vBox->count()) {
            return null;
        }

        // If the vbox occupies just one element in color space, it can't be split
        if (1 === $vBox->count()) {
            return [
                $vBox->copy(),
            ];
        }

        // Select the longest axis for splitting
        $cutColor = $vBox->longestAxis();

        // Find the partial sum arrays along the selected axis.
        [$total, $partialSum] = self::sumColors($cutColor, $histo, $vBox);

        return self::doCut($cutColor, $vBox, $partialSum, $total);
    }

    /**
     * Find the partial sum arrays along the selected axis.
     *
     * @param string          $axis  r|g|b
     * @param array<int, int> $histo
     *
     * @phpstan-param 'r'|'g'|'b' $axis
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
        [$firstRange, $secondRange, $thirdRange] = self::getVBoxColorRanges($vBox, $colorIterateOrder);

        foreach ($firstRange as $firstColor) {
            $sum = 0;
            foreach ($secondRange as $secondColor) {
                foreach ($thirdRange as $thirdColor) {
                    // Rearrange color components
                    /** @var array{r: int, g: int, b: int} $bucket */
                    $bucket = [
                        $colorIterateOrder[0] => $firstColor,
                        $colorIterateOrder[1] => $secondColor,
                        $colorIterateOrder[2] => $thirdColor,
                    ];

                    // The getColorIndex function takes RGB values instead of buckets. The left shift converts our bucket into its RGB value.
                    $bucketIndex = self::getColorIndex(
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
     *
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
        $nColors = $priorityQueue->size();
        $nIterations = 0;

        while ($nIterations < self::MAX_ITERATIONS) {
            if ($nColors >= $target) {
                return;
            }

            ++$nIterations;

            $vBox = $priorityQueue->pop();
            if (null === $vBox) {
                // Logic error: should not happen!
                throw new RuntimeException('Failed to pop VBox from an empty queue.');
            }

            if (!$vBox->count()) { /* just put it back */
                $priorityQueue->push($vBox);
                continue;
            }
            // do the cut
            $vBoxes = self::medianCutApply($histo, $vBox);

            if (!(\is_array($vBoxes) && isset($vBoxes[0]))) {
                // Expect an array of VBox
                throw new RuntimeException('Unexpected result from the medianCutApply function.');
            }

            $priorityQueue->push($vBoxes[0]);

            if (isset($vBoxes[1])) { /* vbox2 can be null */
                $priorityQueue->push($vBoxes[1]);
                ++$nColors;
            }
        }
    }
}
