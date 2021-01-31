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
        $this->assertIsResource($image);
        $this->assertSame('gd', get_resource_type($image));
    }

    public function testLoadInvalidArgument()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Passed variable is not a valid GD resource');

        // We want to check also the specific exception message.
        parent::testLoadInvalidArgument();
    }

    /**
     * @see Issue #30
     */
    public function testLoadFileJpgCorrupted()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('is not readable or does not exists');

        return $this->baseTestLoadFile(__DIR__ . '/../../images/corrupted_PR30.jpg');
    }
}
