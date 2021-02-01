<?php

namespace ColorThief\Image\Adapter\Test;

use ColorThief\Image\Adapter\GDImageAdapter;
use ColorThief\Image\Adapter\IImageAdapter;

/**
 * @requires extension gd
 */
class GDImageAdapterTest extends BaseImageAdapterTest
{
    protected function getTestResourceInstance()
    {
        return imagecreate(80, 20);
    }

    protected function getAdapterInstance(): IImageAdapter
    {
        return new GDImageAdapter();
    }

    protected function checkIsLoaded(IImageAdapter $adapter): void
    {
        // Checks object state
        $image = $adapter->getResource();
        $this->assertIsResource($image);
        $this->assertSame('gd', get_resource_type($image));
    }

    public function testLoadInvalidArgument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Passed variable is not a valid GD resource');

        // We want to check also the specific exception message.
        parent::testLoadInvalidArgument();
    }

    /**
     * @see Issue #30
     */
    public function testLoadFileJpgCorrupted(): IImageAdapter
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('is not readable or does not exists');

        return $this->baseTestLoadFile(__DIR__ . '/../../images/corrupted_PR30.jpg');
    }
}
