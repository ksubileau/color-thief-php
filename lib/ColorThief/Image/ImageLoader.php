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

namespace ColorThief\Image;

use ColorThief\Image\Adapter\ImageAdapter;

class ImageLoader
{
    /**
     * @param mixed $source Path/URL to the image, GD resource, Imagick instance, or image as binary string
     */
    public function load($source): ImageAdapter
    {
        $image = null;

        if (\is_string($source)) {
            if ($this->isImagickLoaded()) {
                $image = $this->getAdapter('Imagick');
            } elseif ($this->isGmagickLoaded()) {
                $image = $this->getAdapter('Gmagick');
            } else {
                $image = $this->getAdapter('GD');
            }

            // Tries to detect if the source string is a binary string or a path to an existing file
            // This test is based on the way that PHP detects an invalid path and throws a warning
            // saying "xxx expects to be a valid path, string given" (see zend_parse_arg_path_str).
            if (false !== strpos($source, "\0")) {
                // Binary string
                $image->loadBinaryString($source);
            } else {
                // Path or URL
                $is_remote = filter_var($source, \FILTER_VALIDATE_URL);
                if (!$is_remote && (!file_exists($source) || !is_readable($source))) {
                    throw new \RuntimeException("Image '".$source."' is not readable or does not exists.");
                }
                $image->loadFile($source);
            }
        } else {
            if ((\is_resource($source) && 'gd' == get_resource_type($source))) {
                $image = $this->getAdapter('GD');
            } elseif (is_a($source, 'Imagick')) {
                $image = $this->getAdapter('Imagick');
            } elseif (is_a($source, 'Gmagick')) {
                $image = $this->getAdapter('Gmagick');
            } else {
                throw new \InvalidArgumentException('Passed variable is not a valid image source');
            }
            $image->load($source);
        }

        return $image;
    }

    /**
     * Checks if Imagick extension is loaded.
     */
    public function isImagickLoaded(): bool
    {
        return \extension_loaded('imagick');
    }

    /**
     * Checks if Gmagick extension is loaded.
     */
    public function isGmagickLoaded(): bool
    {
        return \extension_loaded('gmagick');
    }

    /**
     * @param string $adapterType
     */
    public function getAdapter($adapterType): ImageAdapter
    {
        $classname = '\\ColorThief\\Image\\Adapter\\'.$adapterType.'ImageAdapter';

        return new $classname();
    }
}
