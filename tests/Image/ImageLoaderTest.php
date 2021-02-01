<?php

namespace ColorThief\Image\Test;

use ColorThief\Image\Adapter\GDImageAdapter;
use ColorThief\Image\Adapter\GmagickImageAdapter;
use ColorThief\Image\Adapter\IImageAdapter;
use ColorThief\Image\Adapter\ImagickImageAdapter;
use ColorThief\Image\ImageLoader;

class ImageLoaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ImageLoader */
    protected $loader;

    protected function setUp(): void
    {
        $this->loader = new ImageLoader();
    }

    protected function getAdapterMock(string $adapterName, string $method, $image)
    {
        /** @phpstan-ignore-next-line */
        $adapter = $this->getMockBuilder("\\ColorThief\\Image\\Adapter\\{$adapterName}ImageAdapter")
            ->setMethods([$method])
            ->getMock();

        $adapter->expects($this->once())
            ->method($method)
            ->with($this->equalTo($image));

        return $adapter;
    }

    protected function getImageLoaderPartialMock(
        IImageAdapter $adapter,
        string $adapterName,
        bool $mockIsImagickLoaded = false,
        bool $isImagickLoaded = false,
        bool $mockIsGmagickLoaded = false,
        bool $isGmagickLoaded = false
    ) {
        $methods = ['getAdapter'];
        if ($mockIsImagickLoaded) {
            $methods[] = 'isImagickLoaded';
        }
        if ($mockIsGmagickLoaded) {
            $methods[] = 'isGmagickLoaded';
        }

        $loader = $this->getMockBuilder(ImageLoader::class)
            ->setMethods($methods)
            ->getMock();

        $loader->expects($this->once())
            ->method('getAdapter')
            ->with($this->equalTo($adapterName))
            ->willReturn($adapter);

        if ($mockIsImagickLoaded) {
            $loader->expects($this->once())
                ->method('isImagickLoaded')
                ->willReturn($isImagickLoaded);
        }

        if ($mockIsGmagickLoaded) {
            $loader->expects($this->any())
                ->method('isGmagickLoaded')
                ->willReturn($isGmagickLoaded);
        }

        return $loader;
    }

    /**
     * @requires extension gd
     */
    public function testLoadGDResource(): void
    {
        $image = imagecreate(18, 18);

        $adapter = $this->getAdapterMock('GD', 'load', $image);

        $loader = $this->getImageLoaderPartialMock($adapter, 'GD');

        $this->assertSame($adapter, $loader->load($image));
    }

    /**
     * @requires extension imagick
     */
    public function testLoadImagickResource(): void
    {
        $image = new \Imagick();

        $adapter = $this->getAdapterMock('Imagick', 'load', $image);

        $loader = $this->getImageLoaderPartialMock($adapter, 'Imagick');

        $this->assertSame($adapter, $loader->load($image));
    }

    public function testLoadInvalidResource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Passed variable is not a valid image source');

        $this->loader->load(42);
    }

    protected function baseTestLoadFile(string $adapterName, bool $isImagickLoaded, bool $isGmagickLoaded, ?string $path = null): void
    {
        if ($path === null) {
            $path = __DIR__ . '/../images/pixels.png';
        }

        $adapter = $this->getAdapterMock($adapterName, 'loadFile', $path);

        $loader = $this->getImageLoaderPartialMock(
            $adapter,
            $adapterName,
            true,
            $isImagickLoaded,
            true,
            $isGmagickLoaded
        );

        $this->assertSame($adapter, $loader->load($path));
    }

    /**
     * @requires extension gd
     */
    public function testLoadFileWithGD(): void
    {
        $this->baseTestLoadFile('GD', false, false);
    }

    /**
     * @requires extension imagick
     */
    public function testLoadFileWithImagick(): void
    {
        $this->baseTestLoadFile('Imagick', true, false);
    }

    /**
     * @requires extension gmagick
     */
    public function testLoadFileWithGmagick(): void
    {
        $this->baseTestLoadFile('Gmagick', false, true);
    }

    public function testLoadFileMissing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not readable or does not exists');

        $this->loader->load('Not a file');
    }

    /**
     * @requires extension gd
     */
    public function testLoadUrlWithGD(): void
    {
        $this->baseTestLoadFile(
            'GD',
            false,
            false,
            'https://raw.githubusercontent.com/ksubileau/color-thief-php/master/tests/images/pixels.png'
        );
    }

    /**
     * @requires extension imagick
     */
    public function testLoadUrlWithImagick(): void
    {
        $this->baseTestLoadFile(
            'Imagick',
            true,
            false,
            'https://raw.githubusercontent.com/ksubileau/color-thief-php/master/tests/images/pixels.png'
        );
    }

    /**
     * @requires extension gmagick
     */
    public function testLoadUrlWithGmagick(): void
    {
        $this->baseTestLoadFile(
            'Gmagick',
            false,
            true,
            'https://raw.githubusercontent.com/ksubileau/color-thief-php/master/tests/images/pixels.png'
        );
    }

    protected function baseTestLoadBinaryString(string $adapterName, bool $isImagickLoaded, bool $isGmagickLoaded, ?string $data = null): void
    {
        if ($data === null) {
            $data = 'iVBORw0KGgoAAAANSUhEUgAAABwAAAASCAMAAAB/2U7WAAAABl'
                . 'BMVEUAAAD///+l2Z/dAAAASUlEQVR4XqWQUQoAIAxC2/0vXZDr'
                . 'EX4IJTRkb7lobNUStXsB0jIXIAMSsQnWlsV+wULF4Avk9fLq2r'
                . '8a5HSE35Q3eO2XP1A1wQkZSgETvDtKdQAAAABJRU5ErkJggg==';
            $data = base64_decode($data);
        }

        $adapter = $this->getAdapterMock($adapterName, 'loadBinaryString', $data);

        $loader = $this->getImageLoaderPartialMock(
            $adapter,
            $adapterName,
            true,
            $isImagickLoaded,
            true,
            $isGmagickLoaded
        );

        $this->assertSame($adapter, $loader->load($data));
    }

    /**
     * @requires extension gd
     */
    public function testLoadBinaryStringWithGD(): void
    {
        $this->baseTestLoadBinaryString('GD', false, false);
    }

    /**
     * @requires extension imagick
     */
    public function testLoadBinaryStringWithImagick(): void
    {
        $this->baseTestLoadBinaryString('Imagick', true, false);
    }

    /**
     * @requires extension gmagick
     */
    public function testLoadBinaryStringWithGmagick(): void
    {
        $this->baseTestLoadBinaryString('Gmagick', false, true);
    }

    public function testGetAdapter(): void
    {
        $this->assertInstanceOf(ImagickImageAdapter::class, $this->loader->getAdapter('Imagick'));

        $this->assertInstanceOf(GDImageAdapter::class, $this->loader->getAdapter('GD'));

        $this->assertInstanceOf(GmagickImageAdapter::class, $this->loader->getAdapter('Gmagick'));
    }
}
