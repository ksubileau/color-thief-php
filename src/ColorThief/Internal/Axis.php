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
 * Represents a color channel axis in the 3D color space.
 *
 * @internal
 */
enum Axis
{
    case X;
    case Y;
    case Z;
}
