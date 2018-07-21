<?php
namespace ColorThief\Image\Adapter\Test;

use ColorThief\Image\Adapter\ImagickImageAdapter;
use Imagick;

/**
 * @requires extension imagick
 */
class ImagickImageAdapterTest extends BaseImageAdapterTest
{
    protected function getTestResourceInstance()
    {
        $imagick = new Imagick();

        // The loader requires a non-empty Imagick object for the color space check
        $imagick->setSize(10, 10);
        $imagick->setColorspace(Imagick::COLORSPACE_SRGB);

        return $imagick;
    }

    protected function getAdapterInstance()
    {
        return new ImagickImageAdapter();
    }

    protected function checkIsLoaded($adapter)
    {
        // Checks object state
        $image = $adapter->getResource();
        $this->assertInstanceOf('\Imagick', $image);
        $this->assertTrue($image->valid());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Passed variable is not an instance of Imagick
     */
    public function testLoadInvalidArgument()
    {
        // We want to check also the specific exception message.
        parent::testLoadInvalidArgument();
    }
}
