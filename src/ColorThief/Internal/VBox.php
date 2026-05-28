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
 * @internal
 */
class VBox
{
    private ?int $volume = null;
    private ?int $count = null;

    /**
     * @phpstan-var ColorRGB|null
     */
    private ?array $avg = null;

    /**
     * @param array<int, int> $histo
     */
    public function __construct(public int $r1, public int $r2, public int $g1, public int $g2, public int $b1, public int $b2, public array $histo)
    {
    }

    public function volume(bool $force = false): int
    {
        if (null === $this->volume || $force) {
            $this->volume = (($this->r2 - $this->r1 + 1) * ($this->g2 - $this->g1 + 1) * ($this->b2 - $this->b1 + 1));
        }

        return $this->volume;
    }

    public function count(bool $force = false): int
    {
        if (null === $this->count || $force) {
            $npix = 0;

            // Select the fastest way (i.e. with the fewest iterations) to count
            // the number of pixels contained in this vbox.
            if ($this->volume() > \count($this->histo)) {
                // Iterate over the histogram if the size of this histogram is lower than the vbox volume
                foreach ($this->histo as $bucketIndex => $count) {
                    $rgbBuckets = Mmcq::getColorsFromIndex($bucketIndex, Mmcq::SIGBITS);
                    if ($this->contains($rgbBuckets, 0)) {
                        $npix += $count;
                    }
                }
            } else {
                // Or iterate over points of the vbox if the size of the histogram is greater than the vbox volume
                for ($redBucket = $this->r1; $redBucket <= $this->r2; ++$redBucket) {
                    for ($greenBucket = $this->g1; $greenBucket <= $this->g2; ++$greenBucket) {
                        for ($blueBucket = $this->b1; $blueBucket <= $this->b2; ++$blueBucket) {
                            // The getColorIndex function takes RGB values instead of buckets. The left shift converts our bucket into its RGB value.
                            $bucketIndex = Mmcq::getColorIndex(
                                $redBucket << Mmcq::RSHIFT,
                                $greenBucket << Mmcq::RSHIFT,
                                $blueBucket << Mmcq::RSHIFT,
                                Mmcq::SIGBITS
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
        return new self($this->r1, $this->r2, $this->g1, $this->g2, $this->b1, $this->b2, $this->histo);
    }

    /**
     * Calculates the average color represented by this VBox.
     *
     * @phpstan-return ColorRGB
     */
    public function avg(bool $force = false): array
    {
        if (null === $this->avg || $force) {
            $ntot = 0;
            $mult = 1 << Mmcq::RSHIFT;
            $rsum = 0;
            $gsum = 0;
            $bsum = 0;

            for ($redBucket = $this->r1; $redBucket <= $this->r2; ++$redBucket) {
                for ($greenBucket = $this->g1; $greenBucket <= $this->g2; ++$greenBucket) {
                    for ($blueBucket = $this->b1; $blueBucket <= $this->b2; ++$blueBucket) {
                        // getColorIndex takes RGB values instead of buckets, so left shift so we get a bucketIndex
                        $bucketIndex = Mmcq::getColorIndex(
                            $redBucket << Mmcq::RSHIFT,
                            $greenBucket << Mmcq::RSHIFT,
                            $blueBucket << Mmcq::RSHIFT,
                            Mmcq::SIGBITS
                        );

                        // The bucket values need to be multiplied by $mult to get the RGB values.
                        // Can't use a left shift here, as we're working with a floating point number to put the value at the bucket's midpoint.
                        $hval = $this->histo[$bucketIndex] ?? 0;
                        $ntot += $hval;
                        $rsum += ($hval * ($redBucket + 0.5) * $mult);
                        $gsum += ($hval * ($greenBucket + 0.5) * $mult);
                        $bsum += ($hval * ($blueBucket + 0.5) * $mult);
                    }
                }
            }

            if (0 !== $ntot) {
                $this->avg = [
                    (int) ($rsum / $ntot),
                    (int) ($gsum / $ntot),
                    (int) ($bsum / $ntot),
                ];
            } else {
                $this->avg = [
                    (int) ($mult * ($this->r1 + $this->r2 + 1) / 2),
                    (int) ($mult * ($this->g1 + $this->g2 + 1) / 2),
                    (int) ($mult * ($this->b1 + $this->b2 + 1) / 2),
                ];

                // Ensure all channel values are less than or equal to 255 (Issue #24)
                $this->avg = array_map(static fn (int $val): int => min($val, 255), $this->avg);
            }
        }

        return $this->avg;
    }

    /**
     * @phpstan-param ColorRGB $rgbValue
     */
    public function contains(array $rgbValue, int $rshift = Mmcq::RSHIFT): bool
    {
        // Get the buckets from the RGB values.
        $redBucket = $rgbValue[0] >> $rshift;
        $greenBucket = $rgbValue[1] >> $rshift;
        $blueBucket = $rgbValue[2] >> $rshift;

        return
            $redBucket >= $this->r1
            && $redBucket <= $this->r2
            && $greenBucket >= $this->g1
            && $greenBucket <= $this->g2
            && $blueBucket >= $this->b1
            && $blueBucket <= $this->b2;
    }

    /**
     * Determines the longest axis.
     *
     * @phpstan-return 'r'|'g'|'b'
     */
    public function longestAxis(): string
    {
        // Color-Width for RGB
        $red = $this->r2 - $this->r1;
        $green = $this->g2 - $this->g1;
        $blue = $this->b2 - $this->b1;

        return $red >= $green && $red >= $blue ? 'r' : ($green >= $red && $green >= $blue ? 'g' : 'b');
    }
}
