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

/** Defines the color-space used by the internal quantizer. */
enum ColorSpace
{
    case Rgb;
    case Oklch;
}
