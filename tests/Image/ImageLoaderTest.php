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
        $isImagickLoaded = false,
        $mockIsGmagickLoaded = false,
        $isGmagickLoaded = false
    ) {
        $methods = array('getAdapter');
        if ($mockIsImagickLoaded) {
            $methods[] = 'isImagickLoaded';
        }
        if ($mockIsGmagickLoaded) {
            $methods[] = 'isGmagickLoaded';
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

        if ($mockIsGmagickLoaded) {
            $loader->expects($this->any())
                ->method('isGmagickLoaded')
                ->will($this->returnValue($isGmagickLoaded));
        }

        return $loader;
    }

    /**
     * @requires extension gd
     */
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
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Passed variable is not a valid image source
     */
    public function testLoadInvalidResource()
    {
        $this->loader->load(42);
    }

    protected function baseTestLoadFile($adapterName, $isImagickLoaded, $isGmagickLoaded, $path = false)
    {
        if ($path === false) {
            $path = __DIR__."/../images/pixels.png";
        }

        $adapter = $this->getAdapterMock($adapterName, 'loadFile', $path);

        $loader = $this->getImageLoaderPartialMock($adapter, $adapterName, true, $isImagickLoaded, true, $isGmagickLoaded);

        $this->assertSame($adapter, $loader->load($path));
    }

    /**
     * @requires extension gd
     */
    public function testLoadFileWithGD()
    {
        $this->baseTestLoadFile('GD', false, false);
    }

    /**
     * @requires extension imagick
     */
    public function testLoadFileWithImagick()
    {
        $this->baseTestLoadFile('Imagick', true, false);
    }

    /**
     * @requires extension gmagick
     */
    public function testLoadFileWithGmagick()
    {
        $this->baseTestLoadFile('Gmagick', false, true);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage not readable or does not exists
     */
    public function testLoadFileMissing()
    {
        $this->loader->load("Not a file");
    }

    /**
     * @requires extension gd
     */
    public function testLoadUrlWithGD()
    {
        $this->baseTestLoadFile(
            'GD',
            false,
            false,
            "https://raw.githubusercontent.com/ksubileau/color-thief-php/master/tests/images/pixels.png"
        );
    }

    /**
     * @requires extension imagick
     */
    public function testLoadUrlWithImagick()
    {
        $this->baseTestLoadFile(
            'Imagick',
            true,
            false,
            "https://raw.githubusercontent.com/ksubileau/color-thief-php/master/tests/images/pixels.png"
        );
    }

    /**
     * @requires extension gmagick
     */
    public function testLoadUrlWithGmagick()
    {
        $this->baseTestLoadFile(
            'Gmagick',
            false,
            true,
            "https://raw.githubusercontent.com/ksubileau/color-thief-php/master/tests/images/pixels.png"
        );
    }

    protected function baseTestLoadBinaryString($adapterName, $isImagickLoaded, $isGmagickLoaded, $data = false)
    {
        if ($data === false) {
            $data = 'iVBORw0KGgoAAAANSUhEUgAAABwAAAASCAMAAAB/2U7WAAAABl'
                . 'BMVEUAAAD///+l2Z/dAAAASUlEQVR4XqWQUQoAIAxC2/0vXZDr'
                . 'EX4IJTRkb7lobNUStXsB0jIXIAMSsQnWlsV+wULF4Avk9fLq2r'
                . '8a5HSE35Q3eO2XP1A1wQkZSgETvDtKdQAAAABJRU5ErkJggg==';
            $data = base64_decode($data);
        }

        $adapter = $this->getAdapterMock($adapterName, 'loadBinaryString', $data);

        $loader = $this->getImageLoaderPartialMock($adapter, $adapterName, true, $isImagickLoaded, true, $isGmagickLoaded);

        $this->assertSame($adapter, $loader->load($data));
    }

    /**
     * @requires extension gd
     */
    public function testLoadBinaryStringWithGD()
    {
        $this->baseTestLoadBinaryString('GD', false, false);
    }

    /**
     * @requires extension imagick
     */
    public function testLoadBinaryStringWithImagick()
    {
        $this->baseTestLoadBinaryString('Imagick', true, false);
    }

    /**
     * @requires extension gmagick
     */
    public function testLoadBinaryStringWithGmagick()
    {
        $this->baseTestLoadBinaryString('Gmagick', false, true);
    }

    public function testGetAdapter()
    {
        $this->assertInstanceOf('\ColorThief\Image\Adapter\ImagickImageAdapter', $this->loader->getAdapter("Imagick"));

        $this->assertInstanceOf('\ColorThief\Image\Adapter\GDImageAdapter', $this->loader->getAdapter("GD"));

        $this->assertInstanceOf('\ColorThief\Image\Adapter\GmagickImageAdapter', $this->loader->getAdapter("Gmagick"));
    }
}
