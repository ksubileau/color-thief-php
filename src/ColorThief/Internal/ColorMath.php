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
 * Pure color-math conversion functions shared by all color classes and PixelColor.
 *
 * All methods are protected static so they are accessible to any class that uses
 * this trait without being part of the public API.
 *
 * @internal
 */
trait ColorMath
{
    /** Convert a single sRGB channel (0-255) to linear light (0-1). */
    protected static function srgbToLinear(int $c): float
    {
        $s = $c / 255;

        return $s <= 0.04045 ? $s / 12.92 : (($s + 0.055) / 1.055) ** 2.4;
    }

    /** Convert a linear-light channel (0-1) back to sRGB (0-255). */
    protected static function linearToSrgb(float $c): int
    {
        $s = $c <= 0.0031308 ? 12.92 * $c : 1.055 * $c ** (1 / 2.4) - 0.055;

        return (int) \round(\max(0, \min(255, $s * 255)));
    }

    /**
     * Relative luminance as defined by WCAG 2.x (0 = black, 1 = white).
     *
     * @param int $r Red channel (0–255)
     * @param int $g Green channel (0–255)
     * @param int $b Blue channel (0–255)
     */
    protected static function rgbLuminance(int $r, int $g, int $b): float
    {
        return 0.2126 * self::srgbToLinear($r)
            + 0.7152 * self::srgbToLinear($g)
            + 0.0722 * self::srgbToLinear($b);
    }

    /**
     * Convert sRGB (0-255 each) to OKLCH.
     *
     * @return array{float, float, float} [L (0-1), C (0-~0.4), H (0-360)]
     */
    protected static function rgbToOklch(int $r, int $g, int $b): array
    {
        $lr = self::srgbToLinear($r);
        $lg = self::srgbToLinear($g);
        $lb = self::srgbToLinear($b);

        // Linear sRGB -> LMS (Oklab M1 matrix)
        $l_ = 0.4122214708 * $lr + 0.5363325363 * $lg + 0.0514459929 * $lb;
        $m_ = 0.2119034982 * $lr + 0.6806995451 * $lg + 0.1073969566 * $lb;
        $s_ = 0.0883024619 * $lr + 0.2817188376 * $lg + 0.6299787005 * $lb;

        // Cube root (LMS -> Lab cone response)
        $l3 = $l_ ** (1 / 3);
        $m3 = $m_ ** (1 / 3);
        $s3 = $s_ ** (1 / 3);

        // Lab cone response -> OKLab
        $L = 0.2104542553 * $l3 + 0.7936177850 * $m3 - 0.0040720468 * $s3;
        $a = 1.9779984951 * $l3 - 2.4285922050 * $m3 + 0.4505937099 * $s3;
        $bLab = 0.0259040371 * $l3 + 0.7827717662 * $m3 - 0.8086757660 * $s3;

        // OKLab -> OKLCH
        $C = \sqrt($a * $a + $bLab * $bLab);
        $H = \atan2($bLab, $a) * (180 / M_PI);
        if ($H < 0) {
            $H += 360;
        }

        return [$L, $C, $H];
    }

    /**
     * Convert OKLCH back to sRGB (0-255 each).
     *
     * @return array{int, int, int} [r, g, b]
     */
    protected static function oklchToRgb(float $l, float $c, float $h): array
    {
        // OKLCH -> OKLab
        $hRad = $h * (M_PI / 180);
        $a = $c * \cos($hRad);
        $bLab = $c * \sin($hRad);

        // OKLab -> LMS cone response
        $l3 = $l + 0.3963377774 * $a + 0.2158037573 * $bLab;
        $m3 = $l - 0.1055613458 * $a - 0.0638541728 * $bLab;
        $s3 = $l - 0.0894841775 * $a - 1.2914855480 * $bLab;

        // Cube (cone response -> LMS)
        $l_ = $l3 ** 3;
        $m_ = $m3 ** 3;
        $s_ = $s3 ** 3;

        // LMS -> linear sRGB (inverse M1)
        $lr = +4.0767416621 * $l_ - 3.3077115913 * $m_ + 0.2309699292 * $s_;
        $lg = -1.2684380046 * $l_ + 2.6097574011 * $m_ - 0.3413193965 * $s_;
        $lb = -0.0041960863 * $l_ - 0.7034186147 * $m_ + 1.7076147010 * $s_;

        return [
            self::linearToSrgb($lr),
            self::linearToSrgb($lg),
            self::linearToSrgb($lb),
        ];
    }

    /**
     * Convert sRGB (0-255 each) to HSL.
     *
     * @return array{float, float, float} [H (0-360), S (0-1), L (0-1)]
     */
    protected static function rgbToHsl(int $r, int $g, int $b): array
    {
        $r1 = $r / 255.0;
        $g1 = $g / 255.0;
        $b1 = $b / 255.0;

        $max = max($r1, $g1, $b1);
        $min = min($r1, $g1, $b1);
        $l = ($max + $min) / 2;
        $h = 0.0;
        $s = 0.0;

        if ($max !== $min) {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

            if ($max === $r1) {
                $h = (($g1 - $b1) / $d + ($g1 < $b1 ? 6 : 0)) / 6;
            } elseif ($max === $g1) {
                $h = (($b1 - $r1) / $d + 2) / 6;
            } elseif ($max === $b1) {
                $h = (($r1 - $g1) / $d + 4) / 6;
            }
        }

        return [$h * 360, $s, $l];
    }

    /**
     * Convert HSL back to sRGB (0-255 each).
     *
     * @return array{int, int, int} [r, g, b]
     */
    protected static function hslToRgb(float $h, float $s, float $l): array
    {
        $h /= 360;

        if (0.0 === $s) {
            $val = (int) round($l * 255);

            return [$val, $val, $val];
        }

        $hue2rgb = static function (float $p, float $q, float $t): float {
            if ($t < 0.0) {
                $t += 1.0;
            }
            if ($t > 1.0) {
                $t -= 1.0;
            }
            if ($t < 1 / 6) {
                return $p + ($q - $p) * 6 * $t;
            }
            if ($t < 1 / 2) {
                return $q;
            }
            if ($t < 2 / 3) {
                return $p + ($q - $p) * (2 / 3 - $t) * 6;
            }

            return $p;
        };

        $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
        $p = 2 * $l - $q;

        return [
            (int) round($hue2rgb($p, $q, $h + 1 / 3) * 255),
            (int) round($hue2rgb($p, $q, $h) * 255),
            (int) round($hue2rgb($p, $q, $h - 1 / 3) * 255),
        ];
    }

    /**
     * Convert sRGB (0-255 each) to HSV.
     *
     * @return array{float, float, float} [H (0-360), S (0-1), V (0-1)]
     */
    protected static function rgbToHsv(int $r, int $g, int $b): array
    {
        $r1 = $r / 255.0;
        $g1 = $g / 255.0;
        $b1 = $b / 255.0;

        $max = max($r1, $g1, $b1);
        $min = min($r1, $g1, $b1);
        $d = $max - $min;

        $v = $max;
        $s = 0.0 === $max ? 0.0 : $d / $max;
        $h = 0.0;

        if (0.0 !== $d) {
            if ($max === $r1) {
                $h = (($g1 - $b1) / $d) + ($g1 < $b1 ? 6.0 : 0.0);
            } elseif ($max === $g1) {
                $h = ($b1 - $r1) / $d + 2.0;
            } else {
                $h = ($r1 - $g1) / $d + 4.0;
            }
            $h /= 6.0;
        }

        return [$h * 360, $s, $v];
    }

    /**
     * Convert HSV back to sRGB (0-255 each).
     *
     * @return array{int, int, int} [r, g, b]
     */
    protected static function hsvToRgb(float $h, float $s, float $v): array
    {
        $h /= 360;

        if (0.0 === $s) {
            $val = (int) round($v * 255);

            return [$val, $val, $val];
        }

        $i = (int) ($h * 6);
        $f = $h * 6 - $i;
        $p = $v * (1 - $s);
        $q = $v * (1 - $f * $s);
        $t = $v * (1 - (1 - $f) * $s);

        [$red, $green, $blue] = match ($i % 6) {
            0 => [$v, $t, $p],
            1 => [$q, $v, $p],
            2 => [$p, $v, $t],
            3 => [$p, $q, $v],
            4 => [$t, $p, $v],
            default => [$v, $p, $q],
        };

        return [(int) round($red * 255), (int) round($green * 255), (int) round($blue * 255)];
    }

    /**
     * Convert sRGB (0–255 each) to CMYK.
     *
     * @return array{float, float, float, float} [C (0–1), M (0–1), Y (0–1), K (0–1)]
     */
    protected static function rgbToCmyk(int $r, int $g, int $b): array
    {
        $r1 = $r / 255.0;
        $g1 = $g / 255.0;
        $b1 = $b / 255.0;

        $k = 1 - max($r1, $g1, $b1);

        if ($k >= 1.0) {
            return [0.0, 0.0, 0.0, 1.0];
        }

        $d = 1 - $k;

        return [
            (1 - $r1 - $k) / $d,
            (1 - $g1 - $k) / $d,
            (1 - $b1 - $k) / $d,
            $k,
        ];
    }

    /**
     * Convert CMYK back to sRGB (0–255 each).
     *
     * @return array{int, int, int} [r, g, b]
     */
    protected static function cmykToRgb(float $c, float $m, float $y, float $k): array
    {
        $r = (int) round(255 * (1 - $c) * (1 - $k));
        $g = (int) round(255 * (1 - $m) * (1 - $k));
        $b = (int) round(255 * (1 - $y) * (1 - $k));

        return [$r, $g, $b];
    }
}
