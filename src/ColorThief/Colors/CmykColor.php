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

namespace ColorThief\Colors;

/**
 * Represents a color in the CMYK colorspace (Cyan / Magenta / Yellow / Key-black).
 */
readonly class CmykColor extends AbstractColor
{
    public function __construct(
        private float $cyan,
        private float $magenta,
        private float $yellow,
        private float $black,
        int $population = 0,
        float $proportion = 0.0,
    ) {
        parent::__construct($population, $proportion);
    }

    /** Cyan channel (0–1). */
    public function cyan(): float
    {
        return $this->cyan;
    }

    /** Magenta channel (0–1). */
    public function magenta(): float
    {
        return $this->magenta;
    }

    /** Yellow channel (0–1). */
    public function yellow(): float
    {
        return $this->yellow;
    }

    /** Key / Black channel (0–1). */
    public function black(): float
    {
        return $this->black;
    }

    public function toRgb(): RgbColor
    {
        [$r, $g, $b] = static::cmykToRgb($this->cyan, $this->magenta, $this->yellow, $this->black);

        return new RgbColor($r, $g, $b, $this->population(), $this->proportion());
    }

    public function toCmyk(): self
    {
        return $this;
    }

    public function toString(): string
    {
        return sprintf(
            'cmyk(%d%%, %d%%, %d%%, %d%%)',
            (int) round($this->cyan * 100),
            (int) round($this->magenta * 100),
            (int) round($this->yellow * 100),
            (int) round($this->black * 100),
        );
    }

    /**
     * Components as [cyan, magenta, yellow, black].
     *
     * @return array{float, float, float, float}
     */
    public function toArray(): array
    {
        return [$this->cyan, $this->magenta, $this->yellow, $this->black];
    }
}
