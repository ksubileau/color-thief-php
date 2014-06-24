<?php

namespace ColorThief\Image\Adapter;

class GDImageAdapter extends ImageAdapter
{
    public function load($resource)
    {
        if (!is_resource($resource) || get_resource_type($resource) != 'gd') {
            throw new \InvalidArgumentException("Passed variable is not a valid GD resource");
        }

        parent::load($resource);
    }

    public function loadFile($file)
    {
        list(, , $type) = @getImageSize($file);
        switch ($type) {
            case IMAGETYPE_GIF:
                $this->resource = imagecreatefromgif($file);
                break;

            case IMAGETYPE_JPEG:
                $this->resource = imagecreatefromjpeg($file);
                break;

            case IMAGETYPE_PNG:
                $this->resource = imagecreatefrompng($file);
                break;

            default:
                throw new \RuntimeException("Image '".$file."' is not readable or does not exists.");
                break;
        }
    }

    public function destroy()
    {
        if ($this->resource) {
            imagedestroy($this->resource);
        }
        parent::destroy();
    }

    public function getHeight()
    {
        return imagesy($this->resource);
    }

    public function getWidth()
    {
        return imagesx($this->resource);
    }

    public function getPixelColor($x, $y)
    {
        $rgba = imagecolorat($this->resource, $x, $y);
        $color = imagecolorsforindex($this->resource, $rgba);
        return (object)$color;
    }
}
