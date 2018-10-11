<?php

namespace ColorThief\Image\Adapter;

class GDImageAdapter extends ImageAdapter
{
    /**
     * The image is a true color image.
     */
    protected $trueColor;

    /**
     * The image is a true color image.
     */
    protected $colorsTotal;

    /**
     * The image is a true color image.
     */
    protected $colorsInImage;


    protected function setImageInfo()
    {
        $this->colorsInImage = [];
        $this->isTrueColor = @imageistruecolor($this->resource);
        $this->colorsTotal = @imagecolorstotal($this->resource);
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource)
    {
        if (!is_resource($resource) || get_resource_type($resource) != 'gd') {
            throw new \InvalidArgumentException('Passed variable is not a valid GD resource');
        }

        parent::load($resource);
        $this->setImageInfo();
    }

    /**
     * {@inheritdoc}
     */
    public function loadBinaryString($data)
    {
        $this->resource = @imagecreatefromstring($data);
        if ($this->resource === false) {
            throw new \InvalidArgumentException('Passed binary string is empty or is not a valid image');
        }
        $this->setImageInfo();
    }

    /**
     * {@inheritdoc}
     */
    public function loadFile($file)
    {
        list(, , $type) = @getimagesize($file);

        switch ($type) {
            case IMAGETYPE_GIF:
                $resource = @imagecreatefromgif($file);
                break;

            case IMAGETYPE_JPEG:
                $resource = @imagecreatefromjpeg($file);
                break;

            case IMAGETYPE_PNG:
                $resource = @imagecreatefrompng($file);
                break;

            case IMAGETYPE_WEBP:
                $resource = @imagecreatefromwebp($file);
                break;

            default:
                throw new \RuntimeException("Image '{$file}' is not readable or does not exists.");
                break;
        }

        if ($resource === false) {
            throw new \RuntimeException("Image '{$file}' is not readable or does not exists.");
        }

        $this->resource = $resource;
        $this->setImageInfo();
    }

    /**
     * {@inheritdoc}
     */
    public function destroy()
    {
        if ($this->resource) {
            imagedestroy($this->resource);
        }
        parent::destroy();
    }

    /**
     * {@inheritdoc}
     */
    public function getHeight()
    {
        return imagesy($this->resource);
    }

    /**
     * {@inheritdoc}
     */
    public function getWidth()
    {
        return imagesx($this->resource);
    }

    /**
     * {@inheritdoc}
     */
    public function getPixelColor($x, $y)
    {
        $rgba = imagecolorat($this->resource, $x, $y);
        if ($rgba < $this->colorsTotal) {
            // RGBA may be a palette color if it is less than the number of palette colors in this image... or it may just be the color black.
            // Cache the color information for these colors to bypass the imagecolorsforindex function (which seems to kill performance).
            if (!isset($this->colorsInImage[$rgba])) {
                $this->colorsInImage[$rgba] = imagecolorsforindex($this->resource, $rgba);
            }
            $color = $this->colorsInImage[$rgba];
        } else {
            $color = [
                'red' => ($rgba >> 16) & 0xff,
                'green' => ($rgba >> 8) & 0xff,
                'blue' => $rgba & 0xff,
                'alpha' => ($rgba >> 24) & 0x7f,
            ];
        }

        return (object) $color;
    }
}
