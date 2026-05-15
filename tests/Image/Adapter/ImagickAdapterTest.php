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
use ColorThief\Image\Adapter\AdapterInterface;
use ColorThief\Image\Adapter\ImagickAdapter;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresPhpExtension('imagick')]
class ImagickAdapterTest extends AbstractAdapterTest
{
    protected function getTestResourceInstance(): \Imagick
    {
        return new \Imagick(__DIR__.'/../../images/white.png');
    }

    protected function getAdapterInstance(): AdapterInterface
    {
        return new ImagickAdapter();
    }

    protected function checkIsLoaded(AdapterInterface $adapter): void
    {
        $image = $adapter->getResource();
        $this->assertInstanceOf('\Imagick', $image);
        $this->assertTrue($image->valid());
    }

    public function testLoadInvalidArgument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument is not an instance of Imagick.');

        parent::testLoadInvalidArgument();
    }

    public function testLoadFileWebp(): AdapterInterface
    {
        if (empty(\Imagick::queryFormats('WEBP'))) {
            $this->markTestSkipped('Imagick was not compiled with support for WebP format.');
        }

        return parent::testLoadFileWebp();
    }
}
