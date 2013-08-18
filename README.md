#Color Thief PHP

A PHP class for grabbing the color palette from an image. Uses PHP and GD library to make it happen.

It's a PHP port of the [Color Thief Javascript library] (http://github.com/lokesh/color-thief), using the MMCQ (modified median cut quantization) algorithm from the [Leptonica library] (http://www.leptonica.com/).

##How to use

###Get the dominant color from an image
```php
include "color-thief-php/ColorThiefPHP.php";
$dominantColor = ColorThiefPHP::getColor($sourceImage);
```
The `$sourceImage` variable must contain either the path (relative or absolute) of the image on the server, or a URL to the image.

```php
ColorThiefPHP::getColor($sourceImage[, $quality=10]) 
returns array(r: num, g: num, b: num)
```

This function returns an array of three integer values, corresponding to the RGB values (Red, Green & Blue) of the dominant color. 

You can pass an additional argument (`$quality`) to adjust the calculation accuracy of the dominant color. 0 is the highest quality settings, 10 is the default. But be aware that there is a trade-off between quality and speed/memory consumption !
If the quality settings are too high (close to 0) relative to the image size (pixel counts), it may **exceed the memory limit** set in the PHP configuration (and computation will be slow).


###Build a color palette from an image

In this example, we build an 8 color palette.

```php
include "color-thief-php/ColorThiefPHP.php";
$palette = ColorThiefPHP::getPalette($sourceImage, 8)
```

Again, the `$sourceImage` variable must contain either the path (relative or absolute) of the image on the server, or a URL to the image.

```php
ColorThiefPHP::getPalette($sourceImage[, $colorCount=10, $quality=10])
returns array(array(num, num, num), array(num, num, num), ... )
```

The `$colorCount` argument determines the size of the palette; the number of colors returned. If not set, it defaults to 10.

The `$quality` argument works as in the previous function.

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
