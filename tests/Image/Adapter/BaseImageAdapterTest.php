<?php

namespace ColorThief\Image\Adapter\Test;

use ColorThief\Image\Adapter\IImageAdapter;

abstract class BaseImageAdapterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return resource
     */
    abstract protected function getTestResourceInstance();

    /**
     * @return IImageAdapter
     */
    abstract protected function getAdapterInstance();

    abstract protected function checkIsLoaded($adapter);

    public function testLoad()
    {
        $image = $this->getTestResourceInstance();
        $adapter = $this->getAdapterInstance();
        $adapter->load($image);

        $this->assertSame($image, $adapter->getResource());

        return $adapter;
    }

    protected function baseTestLoadFile($path)
    {
        // Loads image file
        $adapter = $this->getAdapterInstance();
        $adapter->loadFile($path);

        // Checks object state
        $this->checkIsLoaded($adapter);

        return $adapter;
    }

    public function testLoadFilePng()
    {
        return $this->baseTestLoadFile(__DIR__ . '/../../images/pixels.png');
    }

    public function testLoadFileJpg()
    {
        return $this->baseTestLoadFile(__DIR__ . '/../../images/field_1024x683.jpg');
    }

    public function testLoadFileCmykJpg()
    {
        return $this->baseTestLoadFile(__DIR__ . '/../../images/pixels_cmyk_PR37.jpg');
    }

    public function testLoadFileGif()
    {
        return $this->baseTestLoadFile(__DIR__ . '/../../images/rails_600x406.gif');
    }

    /**
     * @see Issue #13
     */
    public function testLoadUrl()
    {
        return $this->baseTestLoadFile(
            'https://raw.githubusercontent.com/ksubileau/color-thief-php/master/tests/images/pixels.png'
        );
    }

    /**
     * @see Issue #13
     */
    public function testLoad404Url()
    {
        $this->setExpectedException('\RuntimeException', 'not readable or does not exists');

        $adapter = $this->getAdapterInstance();
        $adapter->loadFile('http://example.com/pixels.png');
    }

    public function testLoadFileMissing()
    {
        $this->setExpectedException('\RuntimeException', 'not readable or does not exists');

        $adapter = $this->getAdapterInstance();
        $adapter->loadFile('Not a file');
    }

    public function testLoadInvalidArgument()
    {
        $this->setExpectedException('\InvalidArgumentException');

        $adapter = $this->getAdapterInstance();
        /* @noinspection PhpParamsInspection */
        $adapter->load('test');
    }

    public function testLoadBinaryString()
    {
        $data = 'iVBORw0KGgoAAAANSUhEUgAAABwAAAASCAMAAAB/2U7WAAAABl'
            . 'BMVEUAAAD///+l2Z/dAAAASUlEQVR4XqWQUQoAIAxC2/0vXZDr'
            . 'EX4IJTRkb7lobNUStXsB0jIXIAMSsQnWlsV+wULF4Avk9fLq2r'
            . '8a5HSE35Q3eO2XP1A1wQkZSgETvDtKdQAAAABJRU5ErkJggg==';
        $data = base64_decode($data);

        $adapter = $this->getAdapterInstance();
        $adapter->loadBinaryString($data);

        // Checks object state
        $this->checkIsLoaded($adapter);

        return $adapter;
    }

    public function testLoadBinaryStringInvalidArgument()
    {
        $this->setExpectedException('\InvalidArgumentException');

        $adapter = $this->getAdapterInstance();
        $adapter->loadBinaryString('test');
    }

    /**
     * @depends testLoadFilePng
     *
     * @param \ColorThief\Image\Adapter\IImageAdapter $adapter
     */
    public function testGetHeight($adapter)
    {
        $this->assertSame(5, $adapter->getHeight());
    }

    /**
     * @depends testLoadFilePng
     *
     * @param \ColorThief\Image\Adapter\IImageAdapter $adapter
     */
    public function testGetWidth($adapter)
    {
        $this->assertSame(6, $adapter->getWidth());
    }

    /**
     * @depends testLoadFilePng
     *
     * @param \ColorThief\Image\Adapter\IImageAdapter $adapter
     */
    public function testGetPixelColor($adapter)
    {
        $expected = new \stdClass();
        $expected->red = 100;
        $expected->green = 50;
        $expected->blue = 25;
        $expected->alpha = 0;

        $this->assertEquals($expected, $adapter->getPixelColor(1, 0));

        $expected->alpha = 12;
        $this->assertEquals($expected, $adapter->getPixelColor(1, 1));

        $expected->alpha = 63;
        $this->assertEquals($expected, $adapter->getPixelColor(1, 2));

        $expected->alpha = 114;
        $this->assertEquals($expected, $adapter->getPixelColor(1, 3));

        $expected->red = 255;
        $expected->green = 255;
        $expected->blue = 255;
        $expected->alpha = 127;
        $this->assertEquals($expected, $adapter->getPixelColor(1, 4));
    }

    /**
     * @depends testLoadFileCmykJpg
     *
     * @param \ColorThief\Image\Adapter\IImageAdapter $adapter
     */
    public function testGetPixelColorFromCmykJpg($adapter)
    {
        $expected = new \stdClass();
        $expected->red = 192;
        $expected->green = 0;
        $expected->blue = 0;
        $expected->alpha = 0;

        $this->assertEquals($expected, $adapter->getPixelColor(1, 0));

        $expected->red = 78;
        $expected->green = 255;
        $expected->blue = 1;
        $this->assertEquals($expected, $adapter->getPixelColor(1, 1));

        $expected->red = 255;
        $expected->green = 229;
        $expected->blue = 44;
        $this->assertEquals($expected, $adapter->getPixelColor(0, 2));

        $expected->red = 204;
        $expected->green = 203;
        $expected->blue = 204;
        $this->assertEquals($expected, $adapter->getPixelColor(2, 2));

        $expected->red = 255;
        $expected->green = 255;
        $expected->blue = 255;
        $this->assertEquals($expected, $adapter->getPixelColor(2, 1));
    }

    /**
     * @depends testLoad
     *
     * @param \ColorThief\Image\Adapter\IImageAdapter $adapter
     */
    public function testDestroy($adapter)
    {
        $adapter->destroy();
        $this->assertNull($adapter->getResource());

        // Multiple calls should also work
        $adapter->destroy();
    }
}
