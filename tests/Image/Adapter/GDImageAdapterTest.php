<?php

namespace ColorThief\Image\Adapter\Test;

use ColorThief\Image\Adapter\GDImageAdapter;

/**
 * @requires extension gd
 */
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

    protected function checkIsLoaded($adapter)
    {
        // Checks object state
        $image = $adapter->getResource();
        $this->assertInternalType('resource', $image);
        $this->assertSame('gd', get_resource_type($image));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Passed variable is not a valid GD resource
     */
    public function testLoadInvalidArgument()
    {
        // We want to check also the specific exception message.
        parent::testLoadInvalidArgument();
    }

    /**
     * @see Issue #30
     * @expectedException \RuntimeException
     * @expectedExceptionMessage is not readable or does not exists
     */
    public function testLoadFileJpgCorrupted()
    {
        return $this->baseTestLoadFile(__DIR__ . '/../../images/corrupted_PR30.jpg');
    }
}
