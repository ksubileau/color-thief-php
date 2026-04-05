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

use ColorThief\Exception\InvalidArgumentException;
use ColorThief\Exception\NotReadableException;

class GmagickAdapter extends AbstractAdapter
{
    /** @var \Gmagick|null */
    protected ?object $resource = null;

    public static function isAvailable(): bool
    {
        return extension_loaded('gmagick') && class_exists('Gmagick');
    }

    public function load(mixed $resource): AdapterInterface
    {
        if (!$resource instanceof \Gmagick) {
            throw new InvalidArgumentException('Argument is not an instance of Gmagick.');
        }

        if (\Gmagick::COLORSPACE_CMYK === $resource->getImageColorSpace()) {
            // Leave original object unmodified
            $resource = clone $resource;
            $resource->setImageColorspace(\Gmagick::COLORSPACE_RGB);
        }

        return parent::load($resource);
    }

    public function loadFromBinary(string $data): AdapterInterface
    {
        $resource = new \Gmagick();
        try {
            $resource->readImageBlob($data);
        } catch (\GmagickException $e) {
            throw new NotReadableException('Unable to read image from binary data.', 0, $e);
        }

        return $this->load($resource);
    }

    public function loadFromPath(string $file): AdapterInterface
    {
        $resource = null;
        try {
            $resource = new \Gmagick($file);
        } catch (\GmagickException $e) {
            throw new NotReadableException("Unable to read image from path ({$file}).", 0, $e);
        }

        return $this->load($resource);
    }

    private function gmagick(): \Gmagick
    {
        if (!$this->resource instanceof \Gmagick) {
            throw new \RuntimeException('No image loaded.');
        }

        return $this->resource;
    }

    public function destroy(): void
    {
        if ($this->resource instanceof \Gmagick) {
            $this->resource->clear();
            $this->resource->destroy();
        }
        parent::destroy();
    }

    public function getHeight(): int
    {
        return $this->gmagick()->getimageheight();
    }

    public function getWidth(): int
    {
        return $this->gmagick()->getimagewidth();
    }

    public function getPixelColor(int $x, int $y): \ColorThief\Image\PixelColor
    {
        $cropped = clone $this->gmagick();    // No need to modify the original object.
        /** @var \GmagickPixel[] $histogram */
        $histogram = $cropped->cropImage(1, 1, $x, $y)->getImageHistogram();
        $pixel = array_shift($histogram);

        if (!$pixel instanceof \GmagickPixel) {
            throw new \RuntimeException("Failed to get pixel color at ({$x}, {$y}): empty histogram");
        }

        // Un-normalized values don't give a full range 0-1 alpha channel
        // So we ask for normalized values, and then we un-normalize it ourselves.
        /** @var array{r: float, g: float, b: float} $colorArray */
        $colorArray = $pixel->getColor(true, true);

        return new \ColorThief\Image\PixelColor(
            red: (int) round($colorArray['r'] * 255),
            green: (int) round($colorArray['g'] * 255),
            blue: (int) round($colorArray['b'] * 255),
            alpha: (int) round($pixel->getcolorvalue(\Gmagick::COLOR_OPACITY) * 127),
        );
    }
}
