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

use ColorThief\Exception\InvalidArgumentException;

/**
 * Defines a rectangular region of an image used to restrict color extraction.
 *
 * All coordinates are in pixels. The top-left corner of the image is (0, 0).
 * When width or height is omitted, the region extends to the right or bottom
 * edge of the image respectively.
 */
readonly class ImageRegion
{
    /**
     * @param int      $x      X-coordinate of the top-left corner of the region. Defaults to 0.
     * @param int      $y      Y-coordinate of the top-left corner of the region. Defaults to 0.
     * @param int|null $width  Width of the region in pixels. Defaults to null (image width minus x).
     * @param int|null $height Height of the region in pixels. Defaults to null (image height minus y).
     */
    public function __construct(
        public int $x = 0,
        public int $y = 0,
        public ?int $width = null,
        public ?int $height = null,
    ) {
        if ($this->x < 0) {
            throw new InvalidArgumentException('The x coordinate must be a non-negative integer.');
        }

        if ($this->y < 0) {
            throw new InvalidArgumentException('The y coordinate must be a non-negative integer.');
        }

        if (null !== $this->width && $this->width <= 0) {
            throw new InvalidArgumentException('The width must be a positive integer.');
        }

        if (null !== $this->height && $this->height <= 0) {
            throw new InvalidArgumentException('The height must be a positive integer.');
        }
    }
}
