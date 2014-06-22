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

        $i = new Imagick();
        $success = $i->readImage($file);

        if (!$success) {
            throw new \RuntimeException("Image '".$file."' is not readable or does not exists.");
        }

        $this->resource = $i;
    }

    public function destroy()
    {
        $this->resource->clear();
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

        $colorArray = $pixel->getColor();
        $color = new \stdClass();
        $color->red = $colorArray['r'];
        $color->green = $colorArray['g'];
        $color->blue = $colorArray['b'];
        $colorArray = $pixel->getColor(true);
        $color->alpha = 255 - (int)($colorArray['a'] * 255);
        return $color;
    }
}
