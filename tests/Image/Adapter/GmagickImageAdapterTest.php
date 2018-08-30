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
        // The loader requires a non-empty GMagick object for the color space check
        return new GMagick(__DIR__ . '/../../images/blank.png');
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

    public function testLoadInvalidArgument()
    {
        $this->setExpectedException('\InvalidArgumentException', 'Passed variable is not an instance of Gmagick');

        // We want to check also the specific exception message.
        parent::testLoadInvalidArgument();
    }
}
