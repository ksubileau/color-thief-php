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
    public static function getColorIndex(int $x, int $y, int $z, int $sigBits = self::SIGBITS): int
    {
        return (($x >> (8 - $sigBits)) << (2 * $sigBits)) | (($y >> (8 - $sigBits)) << $sigBits) | ($z >> (8 - $sigBits));
    }

    /**
     * Get 3-channel values from a combined color index.
     *
     * @phpstan-return array{int, int, int}
     */
    public static function getColorsFromIndex(int $index, int $sigBits = self::SIGBITS): array
    {
        $mask = (1 << $sigBits) - 1;

        $x = ($index >> (2 * $sigBits)) & $mask;
        $y = ($index >> $sigBits) & $mask;
        $z = $index & $mask;

        return [$x, $y, $z];
    }

    /**
     * @param int             $numPixels Number of image pixels analyzed
     * @param array<int, int> $histo     Histogram
     *
     * @return array<array{channels: array{int, int, int}, population: int}>
     */
    public static function quantize(int $numPixels, int $maxColors, array &$histo): array
    {
        // Short-circuits
        if (0 === $numPixels) {
            throw new InvalidArgumentException('Zero usable pixels found in image.');
        }
        if ($maxColors < 2 || $maxColors > 256) {
            throw new InvalidArgumentException('The maxColors parameter must be between 2 and 256 inclusive.');
        }
        if (0 === \count($histo)) {
            throw new InvalidArgumentException('Image produced an empty histogram.');
        }

        $vBox = self::vboxFromHistogram($histo);

        /** @var PQueue<VBox> $priorityQueue */
        $priorityQueue = new PQueue(static fn (VBox $a, VBox $b): int => $a->count() <=> $b->count());
        $priorityQueue->push($vBox);

        // First set of colors, sorted by population.
        self::quantizeIter($priorityQueue, self::FRACT_BY_POPULATIONS * $maxColors, $histo);

        // Re-sort by the product of pixel occupancy times the size in color space.
        $priorityQueue->setComparator(static fn (VBox $a, VBox $b): int => ($a->count() * $a->volume()) <=> ($b->count() * $b->volume()));

        // Next set: generate the median cuts using the (npix * vol) sorting.
        self::quantizeIter($priorityQueue, $maxColors, $histo);

        // Calculate final quantized channels with population.
        $colors = $priorityQueue->map(static fn (VBox $vbox): array => [
            'channels' => $vbox->avg(),
            'population' => $vbox->count(),
        ]);

        return array_reverse($colors);
    }

    /**
     * @param array<int, int> $histo
     */
    public static function vboxFromHistogram(array $histo): VBox
    {
        $dimMin = [\PHP_INT_MAX, \PHP_INT_MAX, \PHP_INT_MAX];
        $dimMax = [-\PHP_INT_MAX, -\PHP_INT_MAX, -\PHP_INT_MAX];

        // Find min/max.
        foreach (array_keys($histo) as $bucketIndex) {
            $values = self::getColorsFromIndex($bucketIndex);

            // For each color components
            for ($i = 0; $i < 3; ++$i) {
                if ($values[$i] < $dimMin[$i]) {
                    $dimMin[$i] = $values[$i];
                }
                if ($values[$i] > $dimMax[$i]) {
                    $dimMax[$i] = $values[$i];
                }
            }
        }

        return new VBox($dimMin[0], $dimMax[0], $dimMin[1], $dimMax[1], $dimMin[2], $dimMax[2], $histo);
    }

    /**
     * @param int[] $partialSum
     *
     * @return array{VBox, VBox}|null
     */
    public static function doCut(Axis $axis, VBox $vBox, array $partialSum, int $total): ?array
    {
        $lo = $vBox->getAxisMin($axis);
        $hi = $vBox->getAxisMax($axis);

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

                // Avoid 0-count boxes.
                while ($partialSum[$d2] >= $total && (isset($partialSum[$d2 - 1]) && 0 !== $partialSum[$d2 - 1])) {
                    --$d2;
                }

                $vBox1->setAxisMax($axis, $d2);
                $vBox2->setAxisMin($axis, $d2 + 1);

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
            return [$vBox->copy()];
        }

        // Select the longest axis for splitting
        $cutAxis = $vBox->longestAxis();

        // Find the partial sum arrays along the selected axis.
        [$total, $partialSum] = self::sumColors($cutAxis, $histo, $vBox);

        return self::doCut($cutAxis, $vBox, $partialSum, $total);
    }

    /**
     * Find the partial sum arrays along the selected axis.
     *
     * @param array<int, int> $histo
     *
     * @return array{int, array<int, int>} [$total, $partialSum]
     */
    private static function sumColors(Axis $axis, array $histo, VBox $vBox): array
    {
        $total = 0;
        $partialSum = [];

        // The selected axis should be the first range
        /** @var array{Axis, Axis, Axis} $colorIterateOrder */
        $colorIterateOrder = [$axis, ...array_filter(Axis::cases(), static fn (Axis $a) => $a !== $axis)];

        // Retrieves iteration ranges
        [$firstRange, $secondRange, $thirdRange] = \array_map(
            static fn (Axis $axis): array => [
                $vBox->getAxisMin($axis),
                $vBox->getAxisMax($axis),
            ],
            $colorIterateOrder,
        );

        for ($firstValue = $firstRange[0]; $firstValue <= $firstRange[1]; ++$firstValue) {
            $sum = 0;
            for ($secondValue = $secondRange[0]; $secondValue <= $secondRange[1]; ++$secondValue) {
                for ($thirdValue = $thirdRange[0]; $thirdValue <= $thirdRange[1]; ++$thirdValue) {
                    // Rearrange color components
                    /** @var array{X:int,Y:int,Z:int} $bucket */
                    $bucket = [
                        $colorIterateOrder[0]->name => $firstValue,
                        $colorIterateOrder[1]->name => $secondValue,
                        $colorIterateOrder[2]->name => $thirdValue,
                    ];

                    $bucketIndex = self::getColorIndex(
                        $bucket[Axis::X->name] << self::RSHIFT,
                        $bucket[Axis::Y->name] << self::RSHIFT,
                        $bucket[Axis::Z->name] << self::RSHIFT,
                    );

                    if (isset($histo[$bucketIndex])) {
                        $sum += $histo[$bucketIndex];
                    }
                }
            }
            $total += $sum;
            $partialSum[$firstValue] = $total;
        }

        return [$total, $partialSum];
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

            if (0 === $vBox->count()) { /* just put it back */
                $priorityQueue->push($vBox);
                continue;
            }
            // do the cut
            $vBoxes = self::medianCutApply($histo, $vBox);

            if (!\is_array($vBoxes) || !isset($vBoxes[0])) {
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
