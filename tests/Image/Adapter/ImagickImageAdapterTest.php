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
        return new Imagick();
    }

    protected function getAdapterInstance()
    {
        return new ImagickImageAdapter();
    }

    protected function checkFileIsLoaded($path, $adapter)
    {
        // Checks object state
        $image = $adapter->getResource();
        $this->assertInstanceOf('\Imagick', $image);
        $this->assertTrue($image->valid());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Passed variable is not an instance of Imagick
     */
    public function testLoadInvalidArgument()
    {
        // We want to check also the specific exception message.
        parent::testLoadInvalidArgument();
    }
}
