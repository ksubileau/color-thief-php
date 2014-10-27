<?php
namespace ColorThief\Image\Adapter\Test;

abstract class BaseImageAdapterTest extends \PHPUnit_Framework_TestCase
{
    abstract protected function getTestResourceInstance();

    abstract protected function getAdapterInstance();

    abstract protected function checkFileIsLoaded($path, $adapter);

    public function testLoad()
    {
        $image = $this->getTestResourceInstance();
        $adapter = $this->getAdapterInstance();
        $adapter->load($image);

        $this->assertSame($image, $adapter->getResource());

        return $adapter;
    }

    protected function basetestLoadFile($path)
    {
        // Loads image file
        $adapter = $this->getAdapterInstance();
        $adapter->loadFile($path);

        // Checks object state
        $this->checkFileIsLoaded($path, $adapter);

        return $adapter;
    }

    public function testLoadFilePng()
    {
        return $this->basetestLoadFile(__DIR__."/../../images/pixels.png");
    }

    public function testLoadFileJpg()
    {
        return $this->basetestLoadFile(__DIR__."/../../images/field_1024x683.jpg");
    }

    public function testLoadFileGif()
    {
        return $this->basetestLoadFile(__DIR__."/../../images/rails_600x406.gif");
    }

    /**
     * @see Issue #13
     */
    public function testLoadUrl()
    {
        return $this->basetestLoadFile(
            "https://raw.githubusercontent.com/ksubileau/color-thief-php/master/tests/images/pixels.png"
        );
    }

    /**
     * @see Issue #13
     * @expectedException RuntimeException
     * @expectedExceptionMessage not readable or does not exists
     */
    public function testLoad404Url()
    {
        $adapter = $this->getAdapterInstance();
        $adapter->loadFile("http://example.com/pixels.png");
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage not readable or does not exists
     */
    public function testLoadFileMissing()
    {
        $adapter = $this->getAdapterInstance();
        $adapter->loadFile("Not a file");
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testLoadInvalidArgument()
    {
        $adapter = $this->getAdapterInstance();
        $adapter->load("test");
    }

    /**
     * @depends testLoadFilePng
     */
    public function testGetHeight($adapter)
    {
        $this->assertSame(5, $adapter->getHeight());
    }

    /**
     * @depends testLoadFilePng
     */
    public function testGetWidth($adapter)
    {
        $this->assertSame(6, $adapter->getWidth());
    }

    /**
     * @depends testLoadFilePng
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
     * @depends testLoad
     */
    public function testDestroy($adapter)
    {
        $adapter->destroy();
        $this->assertNull($adapter->getResource());

        // Multiple calls should also work
        $adapter->destroy();
    }
}
