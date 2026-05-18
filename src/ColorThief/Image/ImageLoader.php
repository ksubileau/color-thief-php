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

use ColorThief\Exception\NotReadableException;
use ColorThief\Exception\NotSupportedException;
use ColorThief\Image\Adapter\AdapterInterface;

class ImageLoader
{
    private AdapterInterface|string|null $preferredAdapter = null;

    /**
     * Configure the preferred adapter to use to load images.
     *
     * @param string|AdapterInterface|null $adapter Name of the preferred adapter or adapter instance.
     *                                              If null, the adapter is automatically chosen according to the
     *                                              available extensions.
     */
    public function setPreferredAdapter(AdapterInterface|string|null $adapter): self
    {
        $this->preferredAdapter = $adapter;

        return $this;
    }

    /**
     * Loads an image from given source.
     */
    public function load(mixed $source): AdapterInterface
    {
        $preferredAdapter = $this->preferredAdapter;
        // Select appropriate adapter depending on source type if no preference given
        if (null === $preferredAdapter) {
            if ($source instanceof \GdImage) {
                $preferredAdapter = 'Gd';
            } elseif ($this->isImagick($source)) {
                $preferredAdapter = 'Imagick';
            } elseif ($this->isGmagick($source)) {
                $preferredAdapter = 'Gmagick';
            }
        }

        $image = $this->createAdapter($preferredAdapter);

        return match (true) {
            $this->isGdImage($source),
            $this->isImagick($source),
            $this->isGmagick($source) => $image->load($source),
            $this->isBinary($source) => $image->loadFromBinary($source),
            $this->isFilePath($source) => $image->loadFromPath($source),
            default => throw new NotReadableException('Image source does not exists or is not readable.'),
        };
    }

    /**
     * Creates an adapter instance according to config settings.
     */
    public function createAdapter(AdapterInterface|string|null $preferredAdapter = null): AdapterInterface
    {
        if (null === $preferredAdapter) {
            // Select first available adapter
            if (Adapter\ImagickAdapter::isAvailable()) {
                $preferredAdapter = 'Imagick';
            } elseif (Adapter\GmagickAdapter::isAvailable()) {
                $preferredAdapter = 'Gmagick';
            } elseif (Adapter\GdAdapter::isAvailable()) {
                $preferredAdapter = 'Gd';
            } else {
                throw new NotSupportedException('At least one of GD, Imagick or Gmagick extension must be installed. None of them was found.');
            }
        }

        if (is_string($preferredAdapter)) {
            $adapterName = ucfirst($preferredAdapter);
            $adapterClass = sprintf('\\ColorThief\\Image\\Adapter\\%sAdapter', $adapterName);

            if (!class_exists($adapterClass)) {
                throw new NotSupportedException("Image adapter ({$adapterName}) could not be instantiated.");
            }

            /** @var AdapterInterface $adapter */
            $adapter = new $adapterClass();

            return $adapter;
        }

        return $preferredAdapter;
    }

    /**
     * Determines if given source data is a GD image.
     */
    public function isGdImage(mixed $data): bool
    {
        return \is_object($data) && is_a($data, 'GdImage');
    }

    /**
     * Determines if given source data is an Imagick object.
     */
    public function isImagick(mixed $data): bool
    {
        return \is_object($data) && is_a($data, 'Imagick');
    }

    /**
     * Determines if given source data is a Gmagick object.
     */
    public function isGmagick(mixed $data): bool
    {
        return \is_object($data) && is_a($data, 'Gmagick');
    }

    /**
     * Determines if given source data is a file path.
     *
     * @phpstan-assert-if-true =string $data
     */
    public function isFilePath(mixed $data): bool
    {
        if (is_string($data)) {
            try {
                return is_file($data);
            } catch (\Exception) {
                return false;
            }
        }

        return false;
    }

    /**
     * Determines if given source data is binary data.
     *
     * @phpstan-assert-if-true =string $data
     */
    public function isBinary(mixed $data): bool
    {
        if (is_string($data)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);

            if (false === $finfo) {
                return false;
            }

            $mime = (string) finfo_buffer($finfo, $data);

            if (PHP_VERSION_ID < 80400) {
                finfo_close($finfo);
            }

            return !str_starts_with($mime, 'text') && 'application/x-empty' !== $mime;
        }

        return false;
    }
}
