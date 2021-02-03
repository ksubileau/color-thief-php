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

use Gmagick;

/**
 * @property ?Gmagick $resource
 */
class GmagickImageAdapter extends ImageAdapter
{
    public function load($resource): void
    {
        if (!($resource instanceof Gmagick)) {
            throw new \InvalidArgumentException('Passed variable is not an instance of Gmagick');
        }

        if (Gmagick::COLORSPACE_CMYK == $resource->getImageColorSpace()) {
            // Leave original object unmodified
            $resource = clone $resource;
            $resource->setImageColorspace(Gmagick::COLORSPACE_RGB);
        }

        parent::load($resource);
    }

    public function loadBinaryString(string $data): void
    {
        $resource = new Gmagick();
        try {
            $resource->readImageBlob($data);
        } catch (\GmagickException $e) {
            throw new \InvalidArgumentException('Passed binary string is empty or is not a valid image', 0, $e);
        }
        $this->load($resource);
    }

    public function loadFile(string $file): void
    {
        // GMagick doesn't support HTTPS URL directly, so we download the image with file_get_contents first
        // and then we passed the binary string to GmagickImageAdapter::loadBinaryString().
        if (filter_var($file, \FILTER_VALIDATE_URL)) {
            $image = @file_get_contents($file);
            if (false === $image) {
                throw new \RuntimeException("Image '".$file."' is not readable or does not exists.", 0);
            }

            $this->loadBinaryString($image);

            return;
        }

        $resource = null;
        try {
            $resource = new Gmagick($file);
        } catch (\GmagickException $e) {
            throw new \RuntimeException("Image '".$file."' is not readable or does not exists.", 0, $e);
        }
        $this->load($resource);
    }

    public function destroy(): void
    {
        if ($this->resource) {
            $this->resource->clear();
            $this->resource->destroy();
        }
        parent::destroy();
    }

    public function getHeight(): int
    {
        return $this->resource->getimageheight();
    }

    public function getWidth(): int
    {
        return $this->resource->getimagewidth();
    }

    public function getPixelColor(int $x, int $y): \stdClass
    {
        $cropped = clone $this->resource;    // No need to modify the original object.
        $histogram = $cropped->cropImage(1, 1, $x, $y)->getImageHistogram();
        $pixel = array_shift($histogram);

        // Un-normalized values don't give a full range 0-1 alpha channel
        // So we ask for normalized values, and then we un-normalize it ourselves.
        $colorArray = $pixel->getColor(true, true);
        $color = new \stdClass();
        $color->red = (int) round($colorArray['r'] * 255);
        $color->green = (int) round($colorArray['g'] * 255);
        $color->blue = (int) round($colorArray['b'] * 255);
        $color->alpha = (int) round($pixel->getcolorvalue(\Gmagick::COLOR_OPACITY) * 127);

        return $color;
    }
}
