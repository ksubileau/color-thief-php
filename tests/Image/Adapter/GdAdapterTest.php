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
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\RequiresFunction;

#[RequiresPhpExtension('gd')]
class GdAdapterTest extends AbstractAdapterTest
{
    protected function getTestResourceInstance(): \GdImage|false
    {
        return imagecreate(80, 20);
    }

    protected function getAdapterInstance(): AdapterInterface
    {
        return new GdAdapter();
    }

    protected function checkIsLoaded(AdapterInterface $adapter): void
    {
        $image = $adapter->getResource();
        $this->assertInstanceOf('\GdImage', $image);
    }

    public function testLoadInvalidArgument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument is not an instance of GdImage.');

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

    #[RequiresFunction('imagecreatefromwebp')]
    public function testLoadFileWebp(): AdapterInterface
    {
        return parent::testLoadFileWebp();
    }
}
