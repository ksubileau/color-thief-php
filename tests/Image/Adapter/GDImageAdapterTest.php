<?php
namespace ColorThief\Image\Adapter\Test;

use ColorThief\Image\Adapter\GDImageAdapter;

class GDImageAdapterTest extends BaseImageAdapterTest
{
    protected function getTestResourceInstance()
    {
        return imagecreate(80, 20);
    }

    protected function getAdapterInstance()
    {
        return new GDImageAdapter();
    }

    protected function checkFileIsLoaded($path, $adapter)
    {
        // Checks object state
        $image = $adapter->getResource();
        $this->assertInternalType('resource', $image);
        $this->assertSame('gd', get_resource_type($image));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Passed variable is not a valid GD resource
     */
    public function testLoadInvalidArgument()
    {
        // We want to check also the specific exception message.
        parent::testLoadInvalidArgument();
    }
}
