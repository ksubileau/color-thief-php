# Color Thief PHP Changelog

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
