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

namespace ColorThief\Tests\Image\Adapter;

use ColorThief\Exception\InvalidArgumentException;
use ColorThief\Exception\NotReadableException;
use ColorThief\Image\Adapter\AdapterInterface;
use PHPUnit\Framework\Attributes\Depends;

abstract class AbstractAdapterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return resource|object
     */
    abstract protected function getTestResourceInstance();

    abstract protected function getAdapterInstance(): AdapterInterface;

    abstract protected function checkIsLoaded(AdapterInterface $adapter): void;

    public function testLoad(): AdapterInterface
    {
        $image = $this->getTestResourceInstance();
        $adapter = $this->getAdapterInstance();
        $adapter->load($image);

        $this->assertSame($image, $adapter->getResource());

        return $adapter;
    }

    protected function baseTestLoadFile(string $path): AdapterInterface
    {
        // Loads image file
        $adapter = $this->getAdapterInstance();
        $adapter->loadFromPath($path);

        // Checks object state
        $this->checkIsLoaded($adapter);

        return $adapter;
    }

    public function testLoadFilePng(): AdapterInterface
    {
        return $this->baseTestLoadFile(__DIR__.'/../../images/pixels.png');
    }

    public function testLoadFileJpg(): AdapterInterface
    {
        return $this->baseTestLoadFile(__DIR__.'/../../images/field_1024x683.jpg');
    }

    public function testLoadFileCmykJpg(): AdapterInterface
    {
        return $this->baseTestLoadFile(__DIR__.'/../../images/pixels_cmyk_PR37.jpg');
    }

    public function testLoadFileGif(): AdapterInterface
    {
        return $this->baseTestLoadFile(__DIR__.'/../../images/rails_600x406.gif');
    }

    public function testLoadFileWebp(): AdapterInterface
    {
        return $this->baseTestLoadFile(__DIR__.'/../../images/donuts_PR45.webp');
    }

    public function testLoadFileMissing(): void
    {
        $this->expectException(NotReadableException::class);
        $this->expectExceptionMessage('Unable to read image from path');

        $adapter = $this->getAdapterInstance();
        $adapter->loadFromPath('Not a file');
    }

    public function testLoadInvalidArgument(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $adapter = $this->getAdapterInstance();
        /*
         * @noinspection PhpParamsInspection
         * @phpstan-ignore-next-line
         */
        $adapter->load('test');
    }

    public function testLoadBinaryString(): AdapterInterface
    {
        $data = 'iVBORw0KGgoAAAANSUhEUgAAABwAAAASCAMAAAB/2U7WAAAABl'
            .'BMVEUAAAD///+l2Z/dAAAASUlEQVR4XqWQUQoAIAxC2/0vXZDr'
            .'EX4IJTRkb7lobNUStXsB0jIXIAMSsQnWlsV+wULF4Avk9fLq2r'
            .'8a5HSE35Q3eO2XP1A1wQkZSgETvDtKdQAAAABJRU5ErkJggg==';
        $data = base64_decode($data);

        $adapter = $this->getAdapterInstance();
        $adapter->loadFromBinary($data);

        // Checks object state
        $this->checkIsLoaded($adapter);

        return $adapter;
    }

    public function testLoadBinaryStringInvalidArgument(): void
    {
        $this->expectException(NotReadableException::class);
        $this->expectExceptionMessage('Unable to read image from binary data.');

        $adapter = $this->getAdapterInstance();
        $adapter->loadFromBinary('test');
    }

    #[Depends('testLoadFilePng')]
    public function testGetHeight(AdapterInterface $adapter): void
    {
        $this->assertSame(5, $adapter->getHeight());
    }

    #[Depends('testLoadFilePng')]
    public function testGetWidth(AdapterInterface $adapter): void
    {
        $this->assertSame(6, $adapter->getWidth());
    }

    #[Depends('testLoadFilePng')]
    public function testGetPixelColor(AdapterInterface $adapter): void
    {
        $this->assertEquals(new \ColorThief\Image\PixelColor(100, 50, 25, 0), $adapter->getPixelColor(1, 0));
        $this->assertEquals(new \ColorThief\Image\PixelColor(100, 50, 25, 12), $adapter->getPixelColor(1, 1));
        $this->assertEquals(new \ColorThief\Image\PixelColor(100, 50, 25, 63), $adapter->getPixelColor(1, 2));
        $this->assertEquals(new \ColorThief\Image\PixelColor(100, 50, 25, 114), $adapter->getPixelColor(1, 3));
        $this->assertEquals(new \ColorThief\Image\PixelColor(255, 255, 255, 127), $adapter->getPixelColor(1, 4));
    }

    #[Depends('testLoadFileCmykJpg')]
    public function testGetPixelColorFromCmykJpg(AdapterInterface $adapter): void
    {
        $this->assertEquals(new \ColorThief\Image\PixelColor(192, 0, 0, 0), $adapter->getPixelColor(1, 0));
        $this->assertEquals(new \ColorThief\Image\PixelColor(78, 255, 1, 0), $adapter->getPixelColor(1, 1));
        $this->assertEquals(new \ColorThief\Image\PixelColor(255, 229, 44, 0), $adapter->getPixelColor(0, 2));
        $this->assertEquals(new \ColorThief\Image\PixelColor(204, 203, 204, 0), $adapter->getPixelColor(2, 2));
        $this->assertEquals(new \ColorThief\Image\PixelColor(255, 255, 255, 0), $adapter->getPixelColor(2, 1));
    }

    #[Depends('testLoad')]
    public function testDestroy(AdapterInterface $adapter): void
    {
        $adapter->destroy();
        $this->assertNull($adapter->getResource());

        // Multiple calls should also work
        $adapter->destroy();
    }
}
