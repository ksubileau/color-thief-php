Color Thief PHP
==============

[![Build Status](https://travis-ci.org/ksubileau/color-thief-php.png?branch=master)](https://travis-ci.org/ksubileau/color-thief-php)
[![Latest Stable Version](https://poser.pugx.org/ksubileau/color-thief-php/v/stable.png)](https://packagist.org/packages/ksubileau/color-thief-php)
[![Total Downloads](https://poser.pugx.org/ksubileau/color-thief-php/downloads.png)](https://packagist.org/packages/ksubileau/color-thief-php)
[![Latest Unstable Version](https://poser.pugx.org/ksubileau/color-thief-php/v/unstable.png)](https://packagist.org/packages/ksubileau/color-thief-php)
[![License](https://poser.pugx.org/ksubileau/color-thief-php/license.png)](https://packagist.org/packages/ksubileau/color-thief-php)

A PHP class for **grabbing the color palette** from an image. Uses PHP and GD or Imagick libraries to make it happen.

It's a PHP port of the [Color Thief Javascript library](http://github.com/lokesh/color-thief), using the MMCQ (modified median cut quantization) algorithm from the [Leptonica library](http://www.leptonica.com/).

[**See examples**](http://www.kevinsubileau.fr/projets/color-thief-php?utm_campaign=github&utm_term=color-thief-php_readme)

## Requirements

- PHP >= 5.3
- GD >= 2.0 and/or Imagick >= 2.0
- Support JPEG, PNG and GIF images.

##How to use
###Installing via Composer
The recommended way to install Color Thief is through
[Composer](http://getcomposer.org).

```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php
```

Next, update your project's composer.json file to include Color Thief:

```javascript
{
    "require": {
        "ksubileau/color-thief-php": "~1.1"
    }
}
```

###Get the dominant color from an image
```php
require_once 'vendor/autoload.php';
use ColorThief\ColorThief;
$dominantColor = ColorThief::getColor($sourceImage);
```
The `$sourceImage` variable must contain either the absolute path of the image on the server, a URL to the image, a GD resource containing the image, or an [Imagick](http://www.php.net/manual/en/class.imagick.php) image instance.

```php
ColorThief::getColor($sourceImage[, $quality=10, $area=null])
returns array(r: num, g: num, b: num)
```

This function returns an array of three integer values, corresponding to the RGB values (Red, Green & Blue) of the dominant color.

You can pass an additional argument (`$quality`) to adjust the calculation accuracy of the dominant color. 1 is the highest quality settings, 10 is the default. But be aware that there is a trade-off between quality and speed/memory consumption !
If the quality settings are too high (close to 1) relative to the image size (pixel counts), it may **exceed the memory limit** set in the PHP configuration (and computation will be slow).

You can also pass another additional argument (`$area`) to specify a rectangular area in the image in order to get dominant colors only inside this area. This argument must be an associative array with the following keys :
- `$area['x']` : The x-coordinate of the top left corner of the area. Default to 0.
- `$area['y']` : The y-coordinate of the top left corner of the area. Default to 0.
- `$area['w']` : The width of the area. Default to the width of the image minus x-coordinate.
- `$area['h']` : The height of the area. Default to the height of the image minus y-coordinate.


###Build a color palette from an image

In this example, we build an 8 color palette.

```php
require_once 'vendor/autoload.php';
use ColorThief\ColorThief;
$palette = ColorThief::getPalette($sourceImage, 8);
```

Again, the `$sourceImage` variable must contain either the path (relative or absolute) of the image on the server, or a URL to the image.

```php
ColorThief::getPalette($sourceImage[, $colorCount=10, $quality=10, $area=null])
returns array(array(num, num, num), array(num, num, num), ... )
```

The `$colorCount` argument determines the size of the palette; the number of colors returned. If not set, it defaults to 10.

The `$quality` and `$area` arguments work as in the previous function.

##Credits and license

###Author
by Kevin Subileau
[kevinsubileau.fr](http://www.kevinsubileau.fr/?utm_campaign=github&utm_term=color-thief-php_readme)

Based on the fabulous work done by Lokesh Dhakar
[lokeshdhakar.com](http://www.lokeshdhakar.com)
[twitter.com/lokesh](http://twitter.com/lokesh)

###Thanks
* Lokesh Dhakar - For creating the [original project](http://github.com/lokesh/color-thief).
* Nick Rabinowitz - For creating quantize.js.

###License
Licensed under the [Creative Commons Attribution 2.5 License](http://creativecommons.org/licenses/by/2.5/)

* Free for use in both personal and commercial projects.
* Attribution requires leaving author name, author homepage link, and the license info intact.
