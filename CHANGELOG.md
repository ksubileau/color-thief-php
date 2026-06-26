# Color Thief PHP Changelog

## `3.0.0`

**New features**
- Add support for PHP 8.5 (see #62, thank @joeworkman).
- Modernize codebase with PHP 8.2+ features: `readonly` classes, enums, named arguments, ... (see #62, thank @joeworkman).
- Add `getSwatches()` method that classifies a palette into semantic swatch roles: `vibrant`, `muted`, `darkVibrant`, `darkMuted`, `lightVibrant`, `lightMuted`.
- Add population and proportion to all color objects: each extracted color now carries the number of pixels it represents and its share of the analyzed area.
- Add configurable pixel filtering with new `whiteThreshold`, `alphaThreshold`, and `minSaturation` options.
- Add OKLCH quantization support via a new `colorSpace` option. OKLCH produces more perceptually uniform palettes and is now the default.
- Add `ImageRegion` class to restrict color extraction to a rectangular region of the image, replacing the previous `$area` array parameter.
- Add rich API on output color objects:
    - Colorspace conversions: `toRgb()`, `toHsl()`, `toHsv()`, `toOklch()`, `toCmyk()`.
    - Format exports: CSS notation (`toCss()`), hex string (`toHex()`), packed integer (`toInt()`), component array (`toArray()`).
    - WCAG 2.x accessibility helpers: `luminance()`, `isDark()`, `isLight()`, `textColor()`.

**Bug fixes**
- Validate `getimagesize` return value in `GdAdapter::loadFromPath` (see #62, thank @joeworkman).
- Add null check after `array_shift` in `GmagickAdapter::getPixelColor` (see #62, thank @joeworkman).
  
**Breaking changes**
- Drop support for PHP 7.x, 8.0, and 8.1; now requires 8.2+.
- `ColorThief` is now an instantiable readonly class. Static method calls (`ColorThief::getColor()`, `ColorThief::getPalette()`) are no longer supported; create an instance instead.
- Configuration options `quality` and `preferredAdapter` are now constructor parameters. The `$quality` and `$adapter` positional arguments previously accepted by `getColor()` and `getPalette()` have been removed.
- The `$outputFormat` parameter has been removed. `getColor()` now always returns an `RgbColor` object (or `null`) and `getPalette()` always returns a `ColorPalette<RgbColor>`. Use the conversion methods on the returned objects to get the format you need.
- The `$area` array parameter has been replaced by a typed `ImageRegion` object passed as the `$region` named argument.
- `getRed()`, `getGreen()`, and `getBlue()` on the color object have been replaced by `red()`, `green()`, and `blue()` methods.
- The maximum number of colors that can be extracted is now 20 instead of 256.
- Drop support for loading images from URLs. Passing a URL to `getColor()` or `getPalette()` now throws a `NotReadableException`.
  URL fetching was removed because it can introduce Server-Side Request Forgery (SSRF) vulnerabilities if the URL is not validated before being passed to the library; responsibility for fetching remote images now lies with the caller.
  Fetch the image yourself and pass the binary content instead.
  ```php
  // Before
  $color = ColorThief::getColor('https://example.com/image.jpg');

  // After
  use ColorThief\ColorThief;

  $thief = new ColorThief();
  $data = file_get_contents('https://example.com/image.jpg'); // Ensure you implement appropriate security controls when fetching the image
  $color = $thief->getColor($data);
  ```
- OKLCH is now the default quantization color space. Palette output will differ from previous versions. Set `colorSpace: ColorSpace::Rgb` to restore the previous behavior.
- Behavior change for degenerate images: fully transparent, fully white, and single-color images no longer throw an exception; they now return a palette with a single representative color.

## `2.0.2`

**New features**
- Add support for PHP 8.4 (see #60, thank @Redominus).

## `2.0.1`

**Bug fix**
- Fixes a regression in 2.0.0 that could cause an infinite loop under specific circumstances (see #52).

## `2.0.0`

**New features**
- PHP 8 compatibility (see #48 and #50, thank @Agapanthus).
- Add support for reading WebP images (see #45, thank @mreiden).
- Add support for multiple output color formats (RGB, hexadecimal, integer, array or instances of `ColorThief\Color` class).
- Add support for image adapter selection. You can now choose which image extension to use among GD, Imagick, and Gmagick, or provide a custom image adapter.

**Bug fix**
- Fix bug where `getPalette()` does not always return the requested number of colors (see #5).

**Breaking changes**
- Drop support for PHP 5.x, 7.0, and 7.1; now requires 7.2+.
- Reworked exceptions so that all exceptions now inherit from `ColorThief\Exception\Exception`. 
  Migrating from 1.x may require tweaking exception handling in calling code to avoid unhandled exceptions or preserve error handling logic. See 1bf90f40 for details.

**Noticeable changes**
- Switch to MIT license.
- Fileinfo extension is now required.
- Rework some internal image loading logic.

## `1.4.1`

* Significant performance improvement. Around 30% faster and between 20% and 50% less memory usage (see #44, thank @mreiden).
* Fix incorrect palette with single color images (see #41, thank @mreiden).

## `1.4.0`

 * Drop support for PHP 5.3, now requires 5.4+.
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

 * Add support for area targeting (see #12).
 * Fix error with remote images (see #13, thank @rewmike).
 * Fix minor syntax errors (see #14, thank @grachov).
 * Small performance improvements and code cleanup.

## `1.1.0`

 * Add support for Imagick and GD resources. In addition to the path or URL of the image, now you can also directly pass the GD resource or Imagick instance to the getColor and getPalette methods  (see #10).
 * Fix fatal error with solid white images. An exception is now thrown in this case, allowing the caller to catch it (see #11).
 * Fix possible undefined offset under certain circumstances.
 * Change error handling policy: throw exceptions in case of errors instead of returning false.

## `1.0.0`

 * Initial release
