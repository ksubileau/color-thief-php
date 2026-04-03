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
     */
    public function setPreferredAdapter(AdapterInterface|string|null $adapter): self
    {
        $this->preferredAdapter = $adapter;

        return $this;
    }

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
            $source instanceof \GdImage,
            $this->isImagick($source),
            $this->isGmagick($source) => $image->load($source),
            $this->isBinary($source) => $image->loadFromBinary($source),
            $this->isUrl($source) => $image->loadFromUrl($source),
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

    public function isImagick(mixed $data): bool
    {
        return is_a($data, 'Imagick');
    }

    public function isGmagick(mixed $data): bool
    {
        return is_a($data, 'Gmagick');
    }

    public function isFilePath(mixed $data): bool
    {
        if (is_string($data)) {
            try {
                return is_file($data);
            } catch (\Exception $e) {
                return false;
            }
        }

        return false;
    }

    public function isUrl(mixed $data): bool
    {
        return (bool) filter_var($data, FILTER_VALIDATE_URL);
    }

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

            return 'text' != substr($mime, 0, 4) && 'application/x-empty' != $mime;
        }

        return false;
    }
}
