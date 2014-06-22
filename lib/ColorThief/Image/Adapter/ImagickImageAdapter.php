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
        if ($this->resource)
        {
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
