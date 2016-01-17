<?php
namespace ColorThief\Image\Adapter\Test;

use ColorThief\Image\Adapter\GmagickImageAdapter;
use Gmagick;

/**
 * @requires extension gmagick
 */
class GmagickImageAdapterTest extends BaseImageAdapterTest
{
    protected function getTestResourceInstance()
    {
        return new Gmagick();
    }

    protected function getAdapterInstance()
    {
        return new GmagickImageAdapter();
    }

    protected function checkIsLoaded($adapter)
    {
        // Checks object state
        $image = $adapter->getResource();
        $this->assertInstanceOf('\Gmagick', $image);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Passed variable is not an instance of Gmagick
     */
    public function testLoadInvalidArgument()
    {
        // We want to check also the specific exception message.
        parent::testLoadInvalidArgument();
    }
}
