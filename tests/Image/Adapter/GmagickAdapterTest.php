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
use ColorThief\Image\Adapter\GmagickAdapter;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresPhpExtension('gmagick')]
class GmagickAdapterTest extends AbstractAdapterTest
{
    protected function getTestResourceInstance(): \Gmagick
    {
        return new \Gmagick(__DIR__.'/../../images/white.png');
    }

    protected function getAdapterInstance(): AdapterInterface
    {
        return new GmagickAdapter();
    }

    protected function checkIsLoaded(AdapterInterface $adapter): void
    {
        $image = $adapter->getResource();
        $this->assertInstanceOf('\Gmagick', $image);
    }

    public function testLoadInvalidArgument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument is not an instance of Gmagick.');

        parent::testLoadInvalidArgument();
    }

    public function testLoadFileWebp(): AdapterInterface
    {
        if (empty((new \Gmagick())->queryFormats('WEBP'))) {
            $this->markTestSkipped('Gmagick was not compiled with support for WebP format.');
        }

        return parent::testLoadFileWebp();
    }
}
