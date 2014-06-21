<?php

namespace ColorThief\Image\Adapter;

class GDImage implements IImageAdapter
{
    protected $image;

    public function load($resource) {
        if (!is_resource($resource) || get_resource_type($resource) != 'gd')
            throw new InvalidArgumentException("Passed variable is not a valid GD resource");

        $this->image = $resource;
    }

    public function loadFile($file) {
        list(, , $type) = @getImageSize($file);
        switch ($type) {
            case IMAGETYPE_GIF:
                $this->image = imagecreatefromgif($file);
                break;

            case IMAGETYPE_JPEG:
                $this->image = imagecreatefromjpeg($file);
                break;

            case IMAGETYPE_PNG:
                $this->image = imagecreatefrompng($file);
                break;

            default:
                throw new RuntimeException("Could not read image '".$file."' or format is not recognized.");
                break;
        }
    }

    public function destroy() {
        imagedestroy($this->image);
        $this->image = null;
    }

    public function getHeight() {
        return imagesy($this->image);
    }

    public function getWidth() {
        return imagesx($this->image);
    }

    public function getPixelColor($x, $y) {
        $rgba = imagecolorat($this->image, $x, $y);
        $color = imagecolorsforindex($this->image, $rgba);
        return (object)$color;
    }
}