<?php

namespace ColorThief\Image;

use ColorThief\Image\Adapter\GDImageAdapter;
use ColorThief\Image\Adapter\ImagickImageAdapter;

class ImageLoader
{
    public static function load($source)
    {
        $image = null;

        if (is_string($source)) {
            if (!file_exists($source) || !is_readable($source)) {
                throw new \RuntimeException("Image '".$source."' is not readable or does not exists.");
            }

            if (extension_loaded("imagick")) {
                $image = new ImagickImageAdapter();
            } else {
                $image = new GDImageAdapter();
            }

            $image->loadFile($source);
        } else {
            if ((is_resource($source) && get_resource_type($source) == 'gd')) {
                $image = new GDImageAdapter();
            } elseif (is_a($source, 'Imagick')) {
                $image = new ImagickImageAdapter();
            } else {
                throw new \InvalidArgumentException("Passed variable is not a valid image source");
            }
            $image->load($source);
        }

        return $image;
    }
}
