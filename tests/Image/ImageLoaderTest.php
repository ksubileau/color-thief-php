<?php

/*
 * This file is part of the Color Thief PHP project.
 *
 * (c) Kevin Subileau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ColorThief\Image\Test;

require_once __DIR__.'/../functions.php';

use ColorThief\Exception\NotReadableException;
use ColorThief\Exception\NotSupportedException;
use ColorThief\Image\Adapter\AdapterInterface;
use ColorThief\Image\Adapter\GdAdapter;
use ColorThief\Image\Adapter\GmagickAdapter;
use ColorThief\Image\Adapter\ImagickAdapter;
use ColorThief\Image\ImageLoader;

class ImageLoaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ImageLoader */
    protected $loader;

    /** @var bool|null */
    public static $mockGdAvailability;
    /** @var bool|null */
    public static $mockImagickAvailability;
    /** @var bool|null */
    public static $mockGmagickAvailability;

    protected function setUp(): void
    {
        $this->loader = new ImageLoader();

        self::$mockGdAvailability = null;
        self::$mockImagickAvailability = null;
        self::$mockGmagickAvailability = null;
    }

    protected function getAdapterMock(string $method, $image)
    {
        $adapter = $this->createMock(AdapterInterface::class);

        $adapter->expects($this->once())
            ->method($method)
            ->with($this->equalTo($image))
            ->willReturnSelf();

        return $adapter;
    }

    protected function getImageLoaderPartialMock(?string $preferredAdapter, AdapterInterface $returnedAdapter)
    {
        $loader = $this->getMockBuilder(ImageLoader::class)
            ->onlyMethods(['createAdapter'])
            ->getMock();

        $loader->expects($this->once())
            ->method('createAdapter')
            ->with($this->equalTo($preferredAdapter))
            ->willReturn($returnedAdapter);

        return $loader;
    }

    /**
     * @requires extension gd
     */
    public function testLoadGDResource(): void
    {
        $image = imagecreate(18, 18);

        $adapter = $this->getAdapterMock('load', $image);

        $loader = $this->getImageLoaderPartialMock('Gd', $adapter);

        $this->assertSame($adapter, $loader->load($image));
    }

    /**
     * @requires extension imagick
     */
    public function testLoadImagickResource(): void
    {
        $image = new \Imagick();

        $adapter = $this->getAdapterMock('load', $image);

        $loader = $this->getImageLoaderPartialMock('Imagick', $adapter);

        $this->assertSame($adapter, $loader->load($image));
    }

    /**
     * @requires extension gmagick
     */
    public function testLoadGmagickResource(): void
    {
        $image = new \Gmagick();

        $adapter = $this->getAdapterMock('load', $image);

        $loader = $this->getImageLoaderPartialMock('Gmagick', $adapter);

        $this->assertSame($adapter, $loader->load($image));
    }

    public function testLoadFile(): void
    {
        $path = __DIR__.'/../images/pixels.png';

        $adapter = $this->getAdapterMock('loadFromPath', $path);

        $loader = $this->getImageLoaderPartialMock(
            null,
            $adapter
        );

        $this->assertSame($adapter, $loader->load($path));
    }

    public function testLoadUrl(): void
    {
        $url = 'https://raw.githubusercontent.com/ksubileau/color-thief-php/master/tests/images/pixels.png';

        $adapter = $this->getAdapterMock('loadFromUrl', $url);

        $loader = $this->getImageLoaderPartialMock(
            null,
            $adapter
        );

        $this->assertSame($adapter, $loader->load($url));
    }

    public function testLoadBinaryString(): void
    {
        $data = 'iVBORw0KGgoAAAANSUhEUgAAABwAAAASCAMAAAB/2U7WAAAABl'
            .'BMVEUAAAD///+l2Z/dAAAASUlEQVR4XqWQUQoAIAxC2/0vXZDr'
            .'EX4IJTRkb7lobNUStXsB0jIXIAMSsQnWlsV+wULF4Avk9fLq2r'
            .'8a5HSE35Q3eO2XP1A1wQkZSgETvDtKdQAAAABJRU5ErkJggg==';
        $data = base64_decode($data);

        $adapter = $this->getAdapterMock('loadFromBinary', $data);

        $loader = $this->getImageLoaderPartialMock(null, $adapter);

        $this->assertSame($adapter, $loader->load($data));
    }

    public function testLoadFileMissing(): void
    {
        $this->expectException(NotReadableException::class);
        $this->expectExceptionMessage('Image source does not exists or is not readable.');

        $this->loader->load('Not an image');
    }

    public function testLoadInvalidSource(): void
    {
        $this->expectException(NotReadableException::class);
        $this->expectExceptionMessage('Image source does not exists or is not readable.');

        $this->loader->load(42);
    }

    public function testCreateAdapterGdPreferred(): void
    {
        self::$mockGdAvailability = true;
        self::$mockImagickAvailability = true;
        self::$mockGmagickAvailability = true;
        $this->assertInstanceOf(GdAdapter::class, $this->loader->createAdapter('Gd'));
    }

    public function testCreateAdapterImagickPreferred(): void
    {
        self::$mockGdAvailability = true;
        self::$mockImagickAvailability = true;
        self::$mockGmagickAvailability = true;
        $this->assertInstanceOf(ImagickAdapter::class, $this->loader->createAdapter('Imagick'));
    }

    public function testCreateAdapterGmagickPreferred(): void
    {
        self::$mockGdAvailability = true;
        self::$mockImagickAvailability = true;
        self::$mockGmagickAvailability = true;
        $this->assertInstanceOf(GmagickAdapter::class, $this->loader->createAdapter('Gmagick'));
    }

    public function testCreateAdapterAuto(): void
    {
        self::$mockGdAvailability = true;
        self::$mockImagickAvailability = true;
        self::$mockGmagickAvailability = true;

        // First choice if all drivers are available is Imagick
        $this->assertInstanceOf(ImagickAdapter::class, $this->loader->createAdapter());

        // Fallback to Gmagick if Imagick is not present
        self::$mockImagickAvailability = false;
        $this->assertInstanceOf(GmagickAdapter::class, $this->loader->createAdapter());

        // Fallback to GD if both Imagick and Gmagick are not present
        self::$mockGmagickAvailability = false;
        $this->assertInstanceOf(GdAdapter::class, $this->loader->createAdapter());
    }

    public function testCreateAdapterAutoNoneAvailable(): void
    {
        self::$mockGdAvailability = false;
        self::$mockImagickAvailability = false;
        self::$mockGmagickAvailability = false;

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('At least one of GD, Imagick or Gmagick extension must be installed.');

        $this->loader->createAdapter();
    }

    public function testCreateAdapterFromInstance(): void
    {
        $adapter = $this->getMockBuilder(AdapterInterface::class)->getMock();
        $this->assertSame($adapter, $this->loader->createAdapter($adapter));
    }

    public function testCreateAdapterInvalidType(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Unknown image adapter type.');

        $this->loader->createAdapter(85);
    }

    public function testCreateAdapterInvalidName(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Image adapter (Lorem) could not be instantiated.');

        $this->loader->createAdapter('Lorem');
    }

    /**
     * @requires extension imagick
     */
    public function testIsImagick(): void
    {
        $this->assertTrue($this->loader->isImagick(new \Imagick()));
        $this->assertFalse($this->loader->isImagick(new \stdClass()));
        $this->assertFalse($this->loader->isImagick(null));
    }

    /**
     * @requires extension gmagick
     */
    public function testIsGmagick(): void
    {
        $this->assertTrue($this->loader->isGmagick(new \Gmagick()));
        $this->assertFalse($this->loader->isGmagick(new \stdClass()));
        $this->assertFalse($this->loader->isGmagick(null));
    }

    /**
     * @requires extension gd
     */
    public function testIsGdImage(): void
    {
        $resource = imagecreate(18, 18);
        $this->assertTrue($this->loader->isGdImage($resource));
        $this->assertFalse($this->loader->isGdImage(new \stdClass()));
        $this->assertFalse($this->loader->isGdImage(null));
    }

    public function testIsFilepath(): void
    {
        $this->assertTrue($this->loader->isFilepath(__FILE__));
        $this->assertFalse($this->loader->isFilepath(new \stdClass()));
        $this->assertFalse($this->loader->isFilepath([]));
        $this->assertFalse($this->loader->isFilepath(null));
    }

    public function testIsUrl(): void
    {
        $this->assertTrue($this->loader->isUrl('http://foo.bar'));
        $this->assertFalse($this->loader->isUrl('/is/a/path'));
        $this->assertFalse($this->loader->isUrl(null));
    }

    public function testIsBinary(): void
    {
        $this->assertTrue($this->loader->isBinary(file_get_contents(__DIR__.'/../images/pixels.png')));
        $this->assertFalse($this->loader->isBinary(null));
        $this->assertFalse($this->loader->isBinary(1));
        $this->assertFalse($this->loader->isBinary(0));
        $this->assertFalse($this->loader->isBinary([1, 2, 3]));
        $this->assertFalse($this->loader->isBinary(new \stdClass()));
    }
}
