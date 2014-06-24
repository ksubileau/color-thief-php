<?php

namespace ColorThief\Image\Adapter;

use Imagick;

class ImagickImageAdapter extends ImageAdapter
{
    public function load($resource)
    {
        if (!($resource instanceof Imagick)) {
            throw new \InvalidArgumentException("Passed variable is not an instance of Imagick");
        }

        parent::load($resource);
    }

    public function loadFile($file)
    {
        $this->resource = null;

        try {
            $this->resource = new Imagick($file);
        } catch (\ImagickException $e) {
            throw new \RuntimeException("Image '".$file."' is not readable or does not exists.", 0, $e);
        }
    }

    public function destroy()
    {
        if ($this->resource) {
            $this->resource->clear();
        }
        parent::destroy();
    }

    public function getHeight()
    {
        return $this->resource->getImageHeight();
    }

    public function getWidth()
    {
        return $this->resource->getImageWidth();
    }

    public function getPixelColor($x, $y)
    {
        $pixel = $this->resource->getImagePixelColor($x, $y);

        // Un-normalized values don't give a full range 0-1 alpha channel
        // So we ask for normalized values, and then we un-normalize it ourselves.
        $colorArray = $pixel->getColor(true);
        $color = new \stdClass();
        $color->red = round($colorArray['r'] * 255);
        $color->green = round($colorArray['g'] * 255);
        $color->blue = round($colorArray['b'] * 255);
        $color->alpha = 127 - round($colorArray['a'] * 127);

        return $color;
    }
}
