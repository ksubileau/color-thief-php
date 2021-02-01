<?php

namespace ColorThief\Image\Adapter\Test;

use ColorThief\Image\Adapter\IImageAdapter;
use ColorThief\Image\Adapter\ImagickImageAdapter;
use Imagick;

/**
 * @requires extension imagick
 */
class ImagickImageAdapterTest extends BaseImageAdapterTest
{
    protected function getTestResourceInstance()
    {
        // The loader requires a non-empty Imagick object for the color space check
        return new Imagick(__DIR__ . '/../../images/blank.png');
    }

    protected function getAdapterInstance(): IImageAdapter
    {
        return new ImagickImageAdapter();
    }

    protected function checkIsLoaded(IImageAdapter $adapter): void
    {
        // Checks object state
        $image = $adapter->getResource();
        $this->assertInstanceOf('\Imagick', $image);
        $this->assertTrue($image->valid());
    }

    public function testLoadInvalidArgument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Passed variable is not an instance of Imagick');

        // We want to check also the specific exception message.
        parent::testLoadInvalidArgument();
    }
}
