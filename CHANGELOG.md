# Color Thief PHP Changelog

## `2.0.0`

**New features**
- PHP 8 compatibility (see #48 and #50, thank @Agapanthus).
- Add support for reading WebP images (see #45, thank @mreiden).
- Add support for multiple output color formats (RGB, hexadecimal, integer, array or instances of `ColorThief\Color` class).
- Add support for image adapter selection. You can now choose which image extension to use between GD, Imagick or Gmagick, or provide a custom image adapter.

**Bug fix**
- Fix bug where `getPalette()` does not always return the requested amount of colors (see #5).

**Breaking changes**
- Drop support for PHP 5.x, 7.0 and 7.1, now require 7.2+.
- Reworked exceptions so that all exceptions now inherit from `ColorThief\Exception\Exception`. 
  Migrating from 1.x may require tweaking exception handling in calling code to avoid unhandled exceptions or preserve error handling logic. See 1bf90f40 for details.

**Noticeable changes**
- Switch to MIT license.
- Fileinfo extension is now required.
- Rework some internal image loading logic.

## `1.4.1`

* Significant performance improvement. Around 30% faster and between 20 to 50% less memory usage (see #44, thank @mreiden).
* Fix incorrect palette with single color images (see #41, thank @mreiden).

## `1.4.0`

 * Drop support for PHP 5.3, now require 5.4+.
 * Fix incorrect palette with CMYK images using Imagick or Gmagick (see #37, thank @crishoj).
 * Test against PHP 7.2

## `1.3.1`

 * Improve handling of corrupted images: throw a RuntimeException if GD fails to load image. (see #30, thank @othmar52).
 * Fix invalid color values under certain circumstances (see #24).
 * Use a PSR-4 autoloader (see #28, thank @jbboehr).
 * Test against PHP 7.1 (see #27, thank @jbboehr).

## `1.3.0`

 * Color Thief PHP now officially supports PHP 7 ! (see #19).
 * Add GMagick support (see #15).
 * Add capability to load an image from binary string (see #21).
 * Code rewriting and refactoring, improved documentation (see #22, thank @kisPocok).

## `1.2.0`

 * Add support of area targeting (see #12).
 * Fix error with remote images (see #13, thank @rewmike).
 * Fix minor syntax errors (see #14, thank @grachov).
 * Small performance improvements and code cleanup.

## `1.1.0`

 * Add support for Imagick and GD resources. In addition to the path or URL of the image, now you can also directly pass the GD resource or Imagick instance to the getColor and getPalette methods  (see #10).
 * Fix fatal error with solid white images. An exception is now thrown in this case, allowing the caller to catch it (see #11).
 * Fix possible undefined offset under certain circumstances.
 * Change error handling policy : throw exceptions in case of errors instead of return false.

## `1.0.0`

 * Initial release
