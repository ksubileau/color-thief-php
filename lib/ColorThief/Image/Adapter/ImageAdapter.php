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

namespace ColorThief\Image\Adapter;

/**
 * Base adapter implementation to handle image manipulation.
 */
abstract class ImageAdapter implements IImageAdapter
{
    /**
     * @var object|resource|null the image resource handler
     */
    protected $resource;

    public function load($resource): void
    {
        $this->resource = $resource;
    }

    public function destroy(): void
    {
        $this->resource = null;
    }

    public function getResource()
    {
        return $this->resource;
    }
}
