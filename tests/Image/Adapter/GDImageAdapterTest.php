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

namespace ColorThief\Image\Adapter\Test;

use ColorThief\Image\Adapter\GDImageAdapter;
use ColorThief\Image\Adapter\IImageAdapter;

/**
 * @requires extension gd
 */
class GDImageAdapterTest extends BaseImageAdapterTest
{
    protected function getTestResourceInstance()
    {
        return imagecreate(80, 20);
    }

    protected function getAdapterInstance(): IImageAdapter
    {
        return new GDImageAdapter();
    }

    protected function checkIsLoaded(IImageAdapter $adapter): void
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
        $this->expectException(\InvalidArgumentException::class);
        // We want to check also the specific exception message.
        if (version_compare(\PHP_VERSION, '8.0.0') >= 0) {
            $this->expectExceptionMessage('Passed variable is not an instance of GdImage');
        } else {
            $this->expectExceptionMessage('Passed variable is not a valid GD resource');
        }

        parent::testLoadInvalidArgument();
    }

    /**
     * @see Issue #30
     */
    public function testLoadFileJpgCorrupted(): IImageAdapter
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('is not readable or does not exists');

        return $this->baseTestLoadFile(__DIR__.'/../../images/corrupted_PR30.jpg');
    }

    /**
     * @requires function imagecreatefromwebp
     */
    public function testLoadFileWebp(): IImageAdapter
    {
        return parent::testLoadFileWebp();
    }
}
