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

    protected function baseTestLoadUrl(string $path): AdapterInterface
    {
        // Loads image file
        $adapter = $this->getAdapterInstance();
        $adapter->loadFromUrl($path);

        // Checks object state
        $this->checkIsLoaded($adapter);

        return $adapter;
    }

    /**
     * @see Issue #13
     */
    public function testLoadUrl(): AdapterInterface
    {
        return $this->baseTestLoadUrl(
            'https://raw.githubusercontent.com/ksubileau/color-thief-php/master/tests/images/pixels.png'
        );
    }

    /**
     * @see Issue #13
     */
    public function testLoad404Url(): void
    {
        $this->expectException(NotReadableException::class);
        $this->expectExceptionMessage('Unable to load image from url');

        $adapter = $this->getAdapterInstance();
        $adapter->loadFromUrl('http://example.com/pixels.png');
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

    /**
     * @depends testLoadFilePng
     */
    public function testGetHeight(AdapterInterface $adapter): void
    {
        $this->assertSame(5, $adapter->getHeight());
    }

    /**
     * @depends testLoadFilePng
     */
    public function testGetWidth(AdapterInterface $adapter): void
    {
        $this->assertSame(6, $adapter->getWidth());
    }

    /**
     * @depends testLoadFilePng
     */
    public function testGetPixelColor(AdapterInterface $adapter): void
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
     */
    public function testGetPixelColorFromCmykJpg(AdapterInterface $adapter): void
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
     */
    public function testDestroy(AdapterInterface $adapter): void
    {
        $adapter->destroy();
        $this->assertNull($adapter->getResource());

        // Multiple calls should also work
        $adapter->destroy();
    }
}
