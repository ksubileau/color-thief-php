Color Thief PHP
==============

[![Latest Stable Version](https://img.shields.io/packagist/v/ksubileau/color-thief-php?style=flat-square)](https://packagist.org/packages/ksubileau/color-thief-php)
[![Build Status](https://img.shields.io/github/actions/workflow/status/ksubileau/color-thief-php/tests.yml?style=flat-square)](https://github.com/ksubileau/color-thief-php/actions?query=workflow%3ATests)
[![GitHub issues](https://img.shields.io/github/issues/ksubileau/color-thief-php?style=flat-square)](https://github.com/ksubileau/color-thief-php/issues)
[![Packagist](https://img.shields.io/packagist/dm/ksubileau/color-thief-php?style=flat-square)](https://packagist.org/packages/ksubileau/color-thief-php)
[![License](https://img.shields.io/packagist/l/ksubileau/color-thief-php?style=flat-square)](https://packagist.org/packages/ksubileau/color-thief-php)

A PHP class for **grabbing the color palette** from an image. Uses PHP and GD, Imagick or Gmagick libraries to make it happen.

It's a PHP port of the [Color Thief Javascript library](http://github.com/lokesh/color-thief), using the MMCQ (modified median cut quantization) algorithm from the [Leptonica library](http://www.leptonica.com/).

[**See examples**](http://www.kevinsubileau.fr/projets/color-thief-php?utm_campaign=github&utm_term=color-thief-php_readme)

## Requirements

- PHP >= 7.2 or >= PHP 8.0
- Fileinfo extension
- One or more PHP extensions for image processing:
  - GD >= 2.0
  - Imagick >= 2.0 (but >= 3.0 for CMYK images)
  - Gmagick >= 1.0
- Supports JPEG, PNG, GIF and WEBP images.

## How to use
### Install via Composer
The recommended way to install Color Thief is through
[Composer](http://getcomposer.org):
```bash
composer require ksubileau/color-thief-php
```

### Get the dominant color from an image
```php
require_once 'vendor/autoload.php';
use ColorThief\ColorThief;
$dominantColor = ColorThief::getColor($sourceImage);
```
The `$sourceImage` variable must contain either the absolute path of the image on the server, a URL to the image, a GD resource containing the image, an [Imagick](http://www.php.net/manual/en/class.imagick.php) image instance, a [Gmagick](http://www.php.net/manual/en/class.gmagick.php) image instance, or an image in binary string format.

```php
ColorThief::getColor($sourceImage[, $quality=10, $area=null, $outputFormat='array', $adapter = null])
```

You can pass an additional argument (`$quality`) to adjust the calculation accuracy of the dominant color. 1 is the highest quality settings, 10 is the default. But be aware that there is a trade-off between quality and speed/memory consumption !
If the quality settings are too high (close to 1) relative to the image size (pixel counts), it may **exceed the memory limit** set in the PHP configuration (and computation will be slow).

You can also pass another additional argument (`$area`) to specify a rectangular area in the image in order to get dominant colors only inside this area. This argument must be an associative array with the following keys :
- `$area['x']` : The x-coordinate of the top left corner of the area. Default to 0.
- `$area['y']` : The y-coordinate of the top left corner of the area. Default to 0.
- `$area['w']` : The width of the area. Default to the width of the image minus x-coordinate.
- `$area['h']` : The height of the area. Default to the height of the image minus y-coordinate.

By default, color is returned as an array of three integers representing red, green, and blue values.
You can choose another output format by passing one of the following values to the `$outputFormat` argument :
- `rgb`   : RGB string notation (ex: `rgb(253, 42, 152)`).
- `hex`   : String of the hexadecimal representation (ex: `#fd2a98`).
- `int`   : Integer color value (ex: `16591512`).
- `array` : Default format (ex: `array[253, 42, 152]`).
- `obj`   : Instance of `ColorThief\Color`, for custom processing.

The optional `$adapter` argument lets you choose a preferred image adapter to use to load the image.
By default, the adapter is automatically chosen based on the available extensions and the type of `$sourceImage` 
(e.g. Imagick is used if `$sourceImage` is an Imagick instance).
You can pass one of the `Imagick`, `Gmagick` or `Gd` string to force the use of the corresponding underlying image extension. 
For advanced usage, you can even pass an instance of any class implementing the `AdapterInterface` interface to use a custom image loader.

### Build a color palette from an image

In this example, we build an 8 color palette.

```php
require_once 'vendor/autoload.php';
use ColorThief\ColorThief;
$palette = ColorThief::getPalette($sourceImage, 8);
```

Again, the `$sourceImage` variable must contain either the absolute path of the image on the server, a URL to the image, a GD resource containing the image, an [Imagick](http://www.php.net/manual/en/class.imagick.php) image instance, a [Gmagick](http://www.php.net/manual/en/class.gmagick.php) image instance, or an image in binary string format.

```php
ColorThief::getPalette($sourceImage[, $colorCount=10, $quality=10, $area=null, $outputFormat='array', $adapter = null])
```

The `$colorCount` argument determines the size of the palette; the number of colors returned. If not set, it defaults to 10.

The `$quality`, `$area`, `$outputFormat` and `$adapter` arguments work as in the previous function.

## Credits

### Author
by Kevin Subileau
[kevinsubileau.fr](http://www.kevinsubileau.fr/?utm_campaign=github&utm_term=color-thief-php_readme)

Based on the fabulous work done by Lokesh Dhakar
[lokeshdhakar.com](http://www.lokeshdhakar.com)
[twitter.com/lokesh](http://twitter.com/lokesh)

### Thanks
* Lokesh Dhakar - For creating the [original project](http://github.com/lokesh/color-thief).
* Nick Rabinowitz - For creating quantize.js.
