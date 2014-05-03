<?php

namespace ColorThief;

class VBox
{
    public $r1;
    public $r2;
    public $g1;
    public $g2;
    public $b1;
    public $b2;
    public $histo;

    private $volume = false;
    private $count;
    private $count_set = false;
    private $avg = false;

    public function __construct($r1, $r2, $g1, $g2, $b1, $b2, $histo)
    {
        $this->r1 = $r1;
        $this->r2 = $r2;
        $this->g1 = $g1;
        $this->g2 = $g2;
        $this->b1 = $b1;
        $this->b2 = $b2;
        $this->histo = $histo;
    }

    public function volume($force = false)
    {
        if (! $this->volume || $force) {
            $this->volume = (($this->r2 - $this->r1 + 1) * ($this->g2 - $this->g1 + 1) * ($this->b2 - $this->b1 + 1));
        }

        return $this->volume;
    }

    public function count($force = false)
    {
        if (! $this->count_set || $force) {
            $npix = 0;
            for ($i = $this->r1; $i <= $this->r2; $i++) {
                for ($j = $this->g1; $j <= $this->g2; $j++) {
                    for ($k = $this->b1; $k <= $this->b2; $k++) {
                        $index = ColorThief::getColorIndex($i, $j, $k);
                        if (isset($this->histo[$index])) {
                            $npix += $this->histo[$index];
                        }
                    }
                }
            }
            $this->count = $npix;
            $this->count_set = true;
        }

        return $this->count;
    }

    public function copy()
    {
        return new VBox($this->r1, $this->r2, $this->g1, $this->g2, $this->b1, $this->b2, $this->histo);
    }

    public function avg($force = false)
    {
        if (! $this->avg || $force) {
            $ntot = 0;
            $mult = 1 << (8 - ColorThief::SIGBITS);
            $rsum = 0;
            $gsum = 0;
            $bsum = 0;

            for ($i = $this->r1; $i <= $this->r2; $i++) {
                for ($j = $this->g1; $j <= $this->g2; $j++) {
                    for ($k = $this->b1; $k <= $this->b2; $k++) {
                        $histoindex = ColorThief::getColorIndex($i, $j, $k);
                        $hval = isset ($this->histo[$histoindex]) ? $this->histo[$histoindex] : 0;
                        $ntot += $hval;
                        $rsum += ($hval * ($i + 0.5) * $mult);
                        $gsum += ($hval * ($j + 0.5) * $mult);
                        $bsum += ($hval * ($k + 0.5) * $mult);
                    }
                }
            }

            if ($ntot) {
                $this->avg = array (
                        ~ ~ ($rsum / $ntot),
                        ~ ~ ($gsum / $ntot),
                        ~ ~ ($bsum / $ntot)
                );
            } else {
                // echo 'empty box'."\n";
                $this->avg = array (
                        ~ ~ ($mult * ($this->r1 + $this->r2 + 1) / 2),
                        ~ ~ ($mult * ($this->g1 + $this->g2 + 1) / 2),
                        ~ ~ ($mult * ($this->b1 + $this->b2 + 1) / 2)
                );
            }
        }

        return $this->avg;
    }

    public function contains(array $pixel)
    {
        $rval = $pixel[0] >> ColorThief::RSHIFT;
        $gval = $pixel[1] >> ColorThief::RSHIFT;
        $bval = $pixel[2] >> ColorThief::RSHIFT;

        return
            $rval >= $this->r1 &&
            $rval <= $this->r2 &&
            $gval >= $this->g1 &&
            $gval <= $this->g2 &&
            $bval >= $this->b1 &&
            $bval <= $this->b2;
    }
}
