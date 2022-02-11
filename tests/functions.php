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

namespace ColorThief\Image\Adapter;

use ColorThief\Image\Test\ImageLoaderTest;

function function_exists($name)
{
    if ('gd_info' === $name && null !== ImageLoaderTest::$mockGdAvailability) {
        return ImageLoaderTest::$mockGdAvailability;
    }

    return \function_exists($name);
}

function class_exists($name)
{
    if ('Imagick' === $name && null !== ImageLoaderTest::$mockImagickAvailability) {
        return ImageLoaderTest::$mockImagickAvailability;
    }
    if ('Gmagick' === $name && null !== ImageLoaderTest::$mockGmagickAvailability) {
        return ImageLoaderTest::$mockGmagickAvailability;
    }

    return \class_exists($name);
}

function extension_loaded($name)
{
    if ('gd' === $name && null !== ImageLoaderTest::$mockGdAvailability) {
        return ImageLoaderTest::$mockGdAvailability;
    }
    if ('imagick' === $name && null !== ImageLoaderTest::$mockImagickAvailability) {
        return ImageLoaderTest::$mockImagickAvailability;
    }
    if ('gmagick' === $name && null !== ImageLoaderTest::$mockGmagickAvailability) {
        return ImageLoaderTest::$mockGmagickAvailability;
    }

    return \extension_loaded($name);
}
