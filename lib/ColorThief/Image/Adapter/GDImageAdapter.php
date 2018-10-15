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

/**
 * @property ?resource $resource
 */
class GDImageAdapter extends ImageAdapter
{
    public function load($resource): void
    {
        if (!\is_resource($resource) || 'gd' != get_resource_type($resource)) {
            throw new \InvalidArgumentException('Passed variable is not a valid GD resource');
        }

        parent::load($resource);
    }

    public function loadBinaryString(string $data): void
    {
        $resource = @imagecreatefromstring($data);
        if (false === $resource) {
            throw new \InvalidArgumentException('Passed binary string is empty or is not a valid image');
        }
        $this->resource = $resource;
    }

    public function loadFile(string $file): void
    {
        [, , $type] = @getimagesize($file);

        switch ($type) {
            case \IMAGETYPE_GIF:
                $resource = @imagecreatefromgif($file);
                break;

            case \IMAGETYPE_JPEG:
                $resource = @imagecreatefromjpeg($file);
                break;

            case \IMAGETYPE_PNG:
                $resource = @imagecreatefrompng($file);
                break;

            case IMAGETYPE_WEBP:
                $resource = @imagecreatefromwebp($file);
                break;

            default:
                throw new \RuntimeException("Image '{$file}' is not readable or does not exists.");
        }

        if (false === $resource) {
            throw new \RuntimeException("Image '{$file}' is not readable or does not exists.");
        }

        $this->resource = $resource;
    }

    public function destroy(): void
    {
        if ($this->resource) {
            imagedestroy($this->resource);
        }
        parent::destroy();
    }

    public function getHeight(): int
    {
        return imagesy($this->resource);
    }

    public function getWidth(): int
    {
        return imagesx($this->resource);
    }

    public function getPixelColor(int $x, int $y): \stdClass
    {
        $rgba = imagecolorat($this->resource, $x, $y);
        $color = imagecolorsforindex($this->resource, $rgba);

        return (object) $color;
    }
}
