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

/**
 * A 3D box representing a sub-region of the color space,
 * defined by its minimum and maximum bounds on each color channel (X, Y, Z).
 *
 * @internal
 */
class VBox
{
    private ?int $volume = null;
    private ?int $count = null;

    /** @phpstan-var array{int, int, int}|null */
    private ?array $avg = null;

    /**
     * @param array<int, int> $histo
     */
    public function __construct(
        private int $xMin,
        private int $xMax,
        private int $yMin,
        private int $yMax,
        private int $zMin,
        private int $zMax,
        private readonly array $histo,
    ) {
    }

    private function resetCachedValues(): void
    {
        $this->volume = null;
        $this->count = null;
        $this->avg = null;
    }

    public function getAxisMin(Axis $axis): int
    {
        return match ($axis) {
            Axis::X => $this->xMin,
            Axis::Y => $this->yMin,
            Axis::Z => $this->zMin,
        };
    }

    public function getAxisMax(Axis $axis): int
    {
        return match ($axis) {
            Axis::X => $this->xMax,
            Axis::Y => $this->yMax,
            Axis::Z => $this->zMax,
        };
    }

    public function setAxisMin(Axis $axis, int $value): void
    {
        match ($axis) {
            Axis::X => $this->xMin = $value,
            Axis::Y => $this->yMin = $value,
            Axis::Z => $this->zMin = $value,
        };

        $this->resetCachedValues();
    }

    public function setAxisMax(Axis $axis, int $value): void
    {
        match ($axis) {
            Axis::X => $this->xMax = $value,
            Axis::Y => $this->yMax = $value,
            Axis::Z => $this->zMax = $value,
        };

        $this->resetCachedValues();
    }

    public function volume(): int
    {
        if (null === $this->volume) {
            $this->volume = (($this->xMax - $this->xMin + 1) * ($this->yMax - $this->yMin + 1) * ($this->zMax - $this->zMin + 1));
        }

        return $this->volume;
    }

    public function count(): int
    {
        if (null === $this->count) {
            $npix = 0;

            // Select the fastest way (i.e. with the fewest iterations) to count
            // the number of pixels contained in this vbox.
            if ($this->volume() > \count($this->histo)) {
                // Iterate over the histogram if the size of this histogram is lower than the vbox volume
                foreach ($this->histo as $bucketIndex => $count) {
                    $channels = Mmcq::getColorsFromIndex($bucketIndex);
                    if ($this->contains(...$channels)) {
                        $npix += $count;
                    }
                }
            } else {
                // Or iterate over points of the vbox if the size of the histogram is greater than the vbox volume
                for ($dim0 = $this->xMin; $dim0 <= $this->xMax; ++$dim0) {
                    for ($dim1 = $this->yMin; $dim1 <= $this->yMax; ++$dim1) {
                        for ($dim2 = $this->zMin; $dim2 <= $this->zMax; ++$dim2) {
                            // getColorIndex takes channel values in [0,255], so left shift buckets to rebuild 8-bit values.
                            $bucketIndex = Mmcq::getColorIndex(
                                $dim0 << Mmcq::RSHIFT,
                                $dim1 << Mmcq::RSHIFT,
                                $dim2 << Mmcq::RSHIFT,
                            );
                            if (isset($this->histo[$bucketIndex])) {
                                $npix += $this->histo[$bucketIndex];
                            }
                        }
                    }
                }
            }
            $this->count = $npix;
        }

        return $this->count;
    }

    public function copy(): self
    {
        return new self($this->xMin, $this->xMax, $this->yMin, $this->yMax, $this->zMin, $this->zMax, $this->histo);
    }

    /**
     * Calculates the average color represented by this VBox.
     *
     * @phpstan-return array{int, int, int}
     */
    public function avg(bool $force = false): array
    {
        if (null === $this->avg || $force) {
            $ntot = 0;
            $mult = 1 << Mmcq::RSHIFT;
            $sum0 = 0;
            $sum1 = 0;
            $sum2 = 0;

            for ($dim0 = $this->xMin; $dim0 <= $this->xMax; ++$dim0) {
                for ($dim1 = $this->yMin; $dim1 <= $this->yMax; ++$dim1) {
                    for ($dim2 = $this->zMin; $dim2 <= $this->zMax; ++$dim2) {
                        // getColorIndex takes channel values instead of buckets, so left shift to produce bucket index.
                        $bucketIndex = Mmcq::getColorIndex(
                            $dim0 << Mmcq::RSHIFT,
                            $dim1 << Mmcq::RSHIFT,
                            $dim2 << Mmcq::RSHIFT,
                        );

                        // The bucket values need to be multiplied by $mult to get the RGB values.
                        // Can't use a left shift here, as we're working with a floating point number to put the value at the bucket's midpoint.
                        $hval = $this->histo[$bucketIndex] ?? 0;
                        $ntot += $hval;
                        $sum0 += ($hval * ($dim0 + 0.5));
                        $sum1 += ($hval * ($dim1 + 0.5));
                        $sum2 += ($hval * ($dim2 + 0.5));
                    }
                }
            }

            if (0 !== $ntot) {
                $this->avg = [
                    (int) ($mult * $sum0 / $ntot),
                    (int) ($mult * $sum1 / $ntot),
                    (int) ($mult * $sum2 / $ntot),
                ];
            } else {
                $this->avg = [
                    (int) ($mult * ($this->xMin + $this->xMax + 1) / 2),
                    (int) ($mult * ($this->yMin + $this->yMax + 1) / 2),
                    (int) ($mult * ($this->zMin + $this->zMax + 1) / 2),
                ];

                // Ensure all channel values are less than or equal to 255 (Issue #24)
                $this->avg = array_map(static fn (int $val): int => min($val, 255), $this->avg);
            }
        }

        return $this->avg;
    }

    /**
     * Determines if the given coordinates are contained within this VBox.
     */
    public function contains(int $x, int $y, int $z): bool
    {
        return
            $x >= $this->xMin
            && $x <= $this->xMax
            && $y >= $this->yMin
            && $y <= $this->yMax
            && $z >= $this->zMin
            && $z <= $this->zMax;
    }

    /**
     * Determines the longest axis.
     */
    public function longestAxis(): Axis
    {
        $xWidth = $this->xMax - $this->xMin;
        $yWidth = $this->yMax - $this->yMin;
        $zWidth = $this->zMax - $this->zMin;

        return $xWidth >= $yWidth && $xWidth >= $zWidth ? Axis::X : ($yWidth >= $xWidth && $yWidth >= $zWidth ? Axis::Y : Axis::Z);
    }
}
