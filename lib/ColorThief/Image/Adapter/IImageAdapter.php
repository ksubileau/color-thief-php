<?php

declare(strict_types=1);

namespace ColorThief\Image\Adapter;

/**
 * Basic interface for all image adapters.
 */
interface IImageAdapter
{
    /**
     * Loads an image from file.
     */
    public function loadFile(string $path): void;

    /**
     * Loads an image from a binary string representation.
     */
    public function loadBinaryString(string $data): void;

    /**
     * Loads an image resource.
     *
     * @param resource|object $resource
     */
    public function load($resource): void;

    /**
     * Destroys the image.
     */
    public function destroy(): void;

    /**
     * Returns image height.
     */
    public function getHeight(): int;

    /**
     * Returns image width.
     */
    public function getWidth(): int;

    /**
     * Returns the color of the specified pixel.
     */
    public function getPixelColor(int $x, int $y): \stdClass;

    /**
     * Get the raw resource.
     *
     * @return resource|object|null
     */
    public function getResource();
}
