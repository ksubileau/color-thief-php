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
use ColorThief\Image\Adapter\GdAdapter;

/**
 * @requires extension gd
 */
class GdAdapterTest extends AbstractAdapterTest
{
    protected function getTestResourceInstance()
    {
        return imagecreate(80, 20);
    }

    protected function getAdapterInstance(): AdapterInterface
    {
        return new GdAdapter();
    }

    protected function checkIsLoaded(AdapterInterface $adapter): void
    {
        // Checks object state
        $image = $adapter->getResource();
        if (version_compare(\PHP_VERSION, '8.0.0') >= 0) {
            $this->assertInstanceOf('\GdImage', $image);
        } else {
            $this->assertIsResource($image);
            $this->assertSame('gd', get_resource_type($image));
        }
    }

    public function testLoadInvalidArgument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // We want to check also the specific exception message.
        if (version_compare(\PHP_VERSION, '8.0.0') >= 0) {
            $this->expectExceptionMessage('Argument is not an instance of GdImage.');
        } else {
            $this->expectExceptionMessage('Argument is not a valid GD resource.');
        }

        parent::testLoadInvalidArgument();
    }

    /**
     * @see Issue #30
     */
    public function testLoadFileJpgCorrupted(): AdapterInterface
    {
        $this->expectException(NotReadableException::class);
        $this->expectExceptionMessage('Unable to decode image from file');

        return $this->baseTestLoadFile(__DIR__.'/../../images/corrupted_PR30.jpg');
    }

    /**
     * @requires function imagecreatefromwebp
     */
    public function testLoadFileWebp(): AdapterInterface
    {
        return parent::testLoadFileWebp();
    }
}
