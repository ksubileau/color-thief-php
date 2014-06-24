<?php
namespace ColorThief\Image\Adapter;

/**
* Basic interface for all image adapters.
*/
interface IImageAdapter
{
    /**
     * Loads an image from file.
     *
     * @param string $file
     */
    public function loadFile($path);

    /**
     * Loads an image ressource.
     *
     * @param mixed $ressource
     */
    public function load($ressource);

    /**
     * Destroys the image.
     *
     * @param string $file
     */
    public function destroy();

    /**
     * Returns image height.
     *
     * @return integer
     */
    public function getHeight();

    /**
     * Returns image width.
     *
     * @return integer
     */
    public function getWidth();

    /**
     * Returns the color of the specified pixel.
     *
     * @param string $file
     */
    public function getPixelColor($x, $y);

    /**
     * Get the raw resource
     *
     * @return mixed
     */
    public function getResource();
}
