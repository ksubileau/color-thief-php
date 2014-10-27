<?php
namespace ColorThief\Image\Test;

use ColorThief\Image\ImageLoader;

class ImageLoaderTest extends \PHPUnit_Framework_TestCase
{
    protected $loader;

    public function setUp()
    {
        $this->loader = new ImageLoader();
    }

    protected function getAdapterMock($adapterName, $method, $image)
    {
        $adapter = $this->getMock('\ColorThief\Image\Adapter\\'.$adapterName.'ImageAdapter', array($method));
        $adapter->expects($this->once())
                ->method($method)
                ->with($this->equalTo($image));
        return $adapter;
    }

    protected function getImageLoaderPartialMock(
        $adapter,
        $adapterName,
        $mockIsImagickLoaded = false,
        $isImagickLoaded = false
    ) {
        $methods = array('getAdapter');
        if ($mockIsImagickLoaded) {
            $methods[] = 'isImagickLoaded';
        }

        $loader = $this->getMock('\ColorThief\Image\ImageLoader', $methods);
        $loader->expects($this->once())
                ->method('getAdapter')
                ->with($this->equalTo($adapterName))
                ->will($this->returnValue($adapter));

        if ($mockIsImagickLoaded) {
            $loader->expects($this->once())
                    ->method('isImagickLoaded')
                    ->will($this->returnValue($isImagickLoaded));
        }

        return $loader;
    }

    public function testLoadGDResource()
    {
        $image = imagecreate(18, 18);

        $adapter = $this->getAdapterMock('GD', 'load', $image);

        $loader = $this->getImageLoaderPartialMock($adapter, 'GD');

        $this->assertSame($adapter, $loader->load($image));
    }

    /**
     * @requires extension imagick
     */
    public function testLoadImagickResource()
    {
        $image = new \Imagick();

        $adapter = $this->getAdapterMock('Imagick', 'load', $image);

        $loader = $this->getImageLoaderPartialMock($adapter, 'Imagick');

        $this->assertSame($adapter, $loader->load($image));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Passed variable is not a valid image source
     */
    public function testLoadInvalidResource()
    {
        $this->loader->load(42);
    }

    protected function basetestLoadFile($adapterName, $isImagickLoaded, $path = false)
    {
        if (!$path) {
            $path = __DIR__."/../images/pixels.png";
        }

        $adapter = $this->getAdapterMock($adapterName, 'loadFile', $path);

        $loader = $this->getImageLoaderPartialMock($adapter, $adapterName, true, $isImagickLoaded);

        $this->assertSame($adapter, $loader->load($path));
    }

    public function testLoadFileWithGD()
    {
        $this->basetestLoadFile('GD', false);
    }

    /**
     * @requires extension imagick
     */
    public function testLoadFileWithImagick()
    {
        $this->basetestLoadFile('Imagick', true);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage not readable or does not exists
     */
    public function testLoadFileMissing()
    {
        $this->loader->load("Not a file");
    }

    public function testLoadUrlWithGD()
    {
        $this->basetestLoadFile(
            'GD',
            false,
            "https://raw.githubusercontent.com/ksubileau/color-thief-php/master/tests/images/pixels.png"
        );
    }

    /**
     * @requires extension imagick
     */
    public function testLoadUrlWithImagick()
    {
        $this->basetestLoadFile(
            'Imagick',
            true,
            "https://raw.githubusercontent.com/ksubileau/color-thief-php/master/tests/images/pixels.png"
        );
    }

    public function testGetAdapter()
    {
        $this->assertInstanceOf('\ColorThief\Image\Adapter\ImagickImageAdapter', $this->loader->getAdapter("Imagick"));

        $this->assertInstanceOf('\ColorThief\Image\Adapter\GDImageAdapter', $this->loader->getAdapter("GD"));
    }
}
