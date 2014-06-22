<?php

namespace ColorThief\Image\Adapter;

use Imagick;

class ImagickImage implements IImageAdapter
{
    protected $image;

    public function load($resource)
    {
        if (!($resource instanceof Imagick)) {
            throw new \InvalidArgumentException("Passed variable is not an instance of Imagick");
        }

        $this->image = $resource;
    }

    public function loadFile($file)
    {
        $this->image = null;

        $i = new Imagick();
        $success = $i->readImage($file);

        if (!$success) {
            throw new \RuntimeException("Could not read image '".$file."' or format is not recognized.");
        }

        $this->image = $i;
    }

    public function destroy()
    {
        $this->image->clear();
        $this->image = null;
    }

    public function getHeight()
    {
        return $this->image->getImageHeight();
    }

    public function getWidth()
    {
        return $this->image->getImageWidth();
    }

    public function getPixelColor($x, $y)
    {
        $pixel = $this->image->getImagePixelColor($x, $y);

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
