<?php

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
