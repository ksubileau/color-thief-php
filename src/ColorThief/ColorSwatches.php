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

namespace ColorThief;

use ColorThief\Colors\AbstractColor;
use ColorThief\Colors\RgbColor;

/**
 * Map of all swatch roles to their matched Swatch (or null if no good match was found).
 *
 * Classification uses OKLCH distance scoring
 */
readonly class ColorSwatches
{
    // ---------------------------------------------------------------------------
    // OKLCH target ranges for each swatch role
    // ---------------------------------------------------------------------------

    /**
     * @var array<string, array{targetL: float, minL: float, maxL: float, targetC: float, minC: float}>
     */
    private const TARGETS = [
        'vibrant' => ['targetL' => 0.65, 'minL' => 0.40, 'maxL' => 0.85, 'targetC' => 0.20, 'minC' => 0.08],
        'muted' => ['targetL' => 0.65, 'minL' => 0.40, 'maxL' => 0.85, 'targetC' => 0.04, 'minC' => 0.00],
        'darkVibrant' => ['targetL' => 0.30, 'minL' => 0.00, 'maxL' => 0.45, 'targetC' => 0.20, 'minC' => 0.08],
        'darkMuted' => ['targetL' => 0.30, 'minL' => 0.00, 'maxL' => 0.45, 'targetC' => 0.04, 'minC' => 0.00],
        'lightVibrant' => ['targetL' => 0.85, 'minL' => 0.70, 'maxL' => 1.00, 'targetC' => 0.20, 'minC' => 0.08],
        'lightMuted' => ['targetL' => 0.85, 'minL' => 0.70, 'maxL' => 1.00, 'targetC' => 0.04, 'minC' => 0.00],
    ];

    private const WEIGHT_L = 6;
    private const WEIGHT_C = 3;
    private const WEIGHT_POP = 1;

    // ---------------------------------------------------------------------------
    // Constructor
    // ---------------------------------------------------------------------------

    public function __construct(
        public ?RgbColor $vibrant = null,
        public ?RgbColor $muted = null,
        public ?RgbColor $darkVibrant = null,
        public ?RgbColor $darkMuted = null,
        public ?RgbColor $lightVibrant = null,
        public ?RgbColor $lightMuted = null,
    ) {
    }

    // ---------------------------------------------------------------------------
    // Factory
    // ---------------------------------------------------------------------------

    /**
     * Classify a palette into semantic swatch roles using OKLCH distance scoring.
     * Each role is matched to the best-scoring palette color; a color can only be
     * assigned to one role (the one where it scores highest).
     *
     * @param ColorPalette<RgbColor> $palette
     */
    public static function fromPalette(ColorPalette $palette): self
    {
        if ($palette->isEmpty()) {
            return new self();
        }

        $maxPopulation = max(1, ...$palette->map(static fn (AbstractColor $color): int => $color->population()));

        // Evaluate each color against each possible swatches
        /** @var array<array{0: string, 1: RgbColor, 2: float}> $scored */
        $scored = [];
        foreach ($palette as $color) {
            foreach (self::TARGETS as $prop => $target) {
                $score = self::scoreColor($color, $target, $maxPopulation);
                if (null !== $score) {
                    $scored[] = [$prop, $color, $score];
                }
            }
        }

        // Sort by descending score (highest-scoring triples first)
        usort($scored, static fn (array $a, array $b): int => $b[2] <=> $a[2]);

        // Greedy assignment: highest-scoring triple wins; each role and color is used at most once.
        /** @var array<string, RgbColor> $result keyed by property name */
        $result = [];
        $usedColors = new \SplObjectStorage();

        foreach ($scored as [$prop, $color]) {
            if (isset($result[$prop]) || $usedColors->contains($color)) {
                continue;
            }
            $result[$prop] = $color;
            $usedColors->attach($color);
        }

        return new self(
            vibrant: $result['vibrant'] ?? null,
            muted: $result['muted'] ?? null,
            darkVibrant: $result['darkVibrant'] ?? null,
            darkMuted: $result['darkMuted'] ?? null,
            lightVibrant: $result['lightVibrant'] ?? null,
            lightMuted: $result['lightMuted'] ?? null,
        );
    }

    // ---------------------------------------------------------------------------
    // Private helpers
    // ---------------------------------------------------------------------------

    /**
     * @param array{targetL: float, minL: float, maxL: float, targetC: float, minC: float} $target
     */
    private static function scoreColor(AbstractColor $color, array $target, int $maxPopulation): ?float
    {
        $oklch = $color->toOklch();
        $luma = $oklch->lightness();
        $chroma = $oklch->chroma();

        // Out of lightness range -> disqualified
        if ($luma < $target['minL'] || $luma > $target['maxL']) {
            return null;
        }
        // Below minimum chroma -> disqualified
        if ($chroma < $target['minC']) {
            return null;
        }

        $lDist = 1 - abs($luma - $target['targetL']);
        $cDist = 1 - min(abs($chroma - $target['targetC']) / 0.2, 1.0);
        $pop = $maxPopulation > 0 ? $color->population() / $maxPopulation : 0.0;

        return $lDist * self::WEIGHT_L + $cDist * self::WEIGHT_C + $pop * self::WEIGHT_POP;
    }
}
