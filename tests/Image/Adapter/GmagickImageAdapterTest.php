<?php

namespace ColorThief\Image\Adapter\Test;

use ColorThief\Image\Adapter\GmagickImageAdapter;
use ColorThief\Image\Adapter\IImageAdapter;
use Gmagick;

/**
 * @requires extension gmagick
 */
class GmagickImageAdapterTest extends BaseImageAdapterTest
{
    protected function getTestResourceInstance()
    {
        // The loader requires a non-empty GMagick object for the color space check
        return new GMagick(__DIR__.'/../../images/blank.png');
    }

    protected function getAdapterInstance(): IImageAdapter
    {
        return new GmagickImageAdapter();
    }

    protected function checkIsLoaded(IImageAdapter $adapter): void
    {
        // Checks object state
        $image = $adapter->getResource();
        $this->assertInstanceOf('\Gmagick', $image);
    }

    public function testLoadInvalidArgument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Passed variable is not an instance of Gmagick');

        // We want to check also the specific exception message.
        parent::testLoadInvalidArgument();
    }
}
