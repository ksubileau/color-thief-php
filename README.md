Color Thief PHP
==============

[![Latest Stable Version](https://img.shields.io/packagist/v/ksubileau/color-thief-php?style=flat-square)](https://packagist.org/packages/ksubileau/color-thief-php)
[![Build Status](https://img.shields.io/github/actions/workflow/status/ksubileau/color-thief-php/tests.yml?style=flat-square)](https://github.com/ksubileau/color-thief-php/actions?query=workflow%3ATests)
[![GitHub issues](https://img.shields.io/github/issues/ksubileau/color-thief-php?style=flat-square)](https://github.com/ksubileau/color-thief-php/issues)
[![Packagist](https://img.shields.io/packagist/dm/ksubileau/color-thief-php?style=flat-square)](https://packagist.org/packages/ksubileau/color-thief-php)
[![License](https://img.shields.io/packagist/l/ksubileau/color-thief-php?style=flat-square)](https://packagist.org/packages/ksubileau/color-thief-php)

A PHP library for grabbing the **dominant color** or a **representative color palette** from an image.

It is a PHP port of the [Color Thief Javascript library](http://github.com/lokesh/color-thief), using the MMCQ (modified
median cut quantization) algorithm from the [Leptonica library](http://www.leptonica.com/).

[**See examples**](http://www.kevinsubileau.fr/projets/color-thief-php?utm_campaign=github&utm_term=color-thief-php_readme)

---

## Why Color Thief?

Extracting meaningful colors from an image unlocks a surprising range of use cases: generating dynamic themes that match
a user's uploaded avatar, building visually coherent product cards from cover art, creating adaptive UI backgrounds for
music or video players, or simply analyzing the dominant hues of a dataset of images.

Color Thief PHP does this for you, without any manual color-picking, and returns results in the color format that suits
you: hex, CSS `rgb()`, HSL, OKLCH, CMYK, or a plain integer. You can ask for a single dominant color, a ranked palette
of up to 20 colors, or semantic _swatches_ (Vibrant, Muted, DarkVibrant, etc.).

---

## Requirements

- PHP **8.2** or higher
- Fileinfo extension
- One or more PHP extensions for image processing:
    - GD >= 2.0
    - Imagick >= 2.0 (but >= 3.0 for CMYK images)
    - Gmagick >= 1.0
- Supports JPEG, PNG, GIF and WEBP images.

---

## Getting started

### Install via Composer

The recommended way to install Color Thief is through
[Composer](http://getcomposer.org):

```bash
composer require ksubileau/color-thief-php
```

### Get the dominant color

All functionality is exposed through the `ColorThief\ColorThief` class. Instantiate it once and reuse it across multiple
images.

```php
use ColorThief\ColorThief;
 
$thief = new ColorThief();
$sourceImage = '/path/to/image.jpg';
$color = $thief->getColor($sourceImage);
 
echo $color->toCss();    // "rgb(98, 42, 131)"
echo $color->toHex('#'); // "#622a83"

```

`getColor()` returns a `RgbColor` object (or `null` if no color could be extracted), with a set of utility methods to
get the color representation that suits you.

### Get a color palette

```php
$palette = $thief->getPalette($sourceImage);
 
foreach ($palette as $color) {
    echo $color->toHex('#') . "\n";
}
// #622a83
// #d4a832
// #1e6b4a
// ...
```

`getPalette()` returns a `ColorPalette` object, which is countable, iterable, and array-accessible. Colors are ordered
from most to least dominant.

### Get semantic swatches

```php
$swatches = $thief->getSwatches($sourceImage);
 
echo $swatches->vibrant?->toHex('#');      // e.g. "#e03c7d"
echo $swatches->darkVibrant?->toHex('#');  // e.g. "#7b1c2e"
echo $swatches->muted?->toHex('#');        // e.g. "#b07a92"
```

`getSwatches()` classifies the palette colors into up to six named roles: `vibrant`, `muted`, `darkVibrant`,
`darkMuted`,
`lightVibrant`, and `lightMuted`. Each property holds an `RgbColor` or `null` if no palette color matched that role.

### Accepted image sources

All three methods accept the same types for the `$sourceImage` parameter:

- A **file path** string: `'/path/to/image.jpg'` (You must ensure that the path is sanitized before calling ColorThief)
- A **GD resource** (`\GdImage`)
- An **Imagick** instance
- A **Gmagick** instance
- Raw image **binary content** as a string

> **Note:** Loading images from URLs is no longer supported in v3. If you need to process a remote image, fetch it
> yourself before passing the content to Color Thief (see [Migrating from v2 to v3](#migrating-from-v2-to-v3)).

---

## Working with Color Objects

Every method returns `RgbColor` objects (or collections of them), with a rich set of chainable conversion and utility
methods.

### Converting a single color

```php
$color = $thief->getColor('/path/to/image.jpg');
 
// CSS representations
$color->toCss();            // "rgb(98, 42, 131)"
$color->toOklch()->toCss(); // "oklch(0.3721 0.1584 307.45)"
$color->toHsl()->toCss();   // "hsl(277, 52%, 34%)"
 
// Hexadecimal
$color->toHex();     // "622a83"
$color->toHex('#');  // "#622a83"
 
// Component arrays
$color->toArray();            // [98, 42, 131]
$color->toHsl()->toArray();   // [276.92, 0.515, 0.339]
$color->toOklch()->toArray(); // [0.3721, 0.1584, 307.45]
$color->toCmyk()->toArray();  // [0.252, 0.679, 0.0, 0.486]
 
// RGB packed integer (r << 16 | g << 8 | b)
$color->toInt(); // 6432387
 
// Accessibility helpers
$color->luminance();  // 0.042 (WCAG 2.x relative luminance)
$color->isDark();     // true
$color->textColor();  // RgbColor(255, 255, 255) — white text on this background
 
// Population data
$color->population();  // 4821  (number of pixels this color represents)
$color->proportion();  // 0.127 (fraction of analyzed pixels, 0-1)
```

Available conversion targets from any color object: `toRgb()`, `toOklch()`, `toHsl()`, `toHsv()`, `toCmyk()`.

### Converting a palette

`ColorPalette` mirrors the same conversion methods, applied to all colors at once:

```php
$palette = $thief->getPalette('/path/to/image.jpg', colorCount: 6);
 
$palette->toHex('#');         // ["#622a83", "#d4a832", "#1e6b4a", ...]
$palette->toCss();            // ["rgb(98, 42, 131)", "rgb(212, 168, 50)", ...]
$palette->toArray();          // [[98,42,131], [212,168,50], ...]
$palette->toInt();            // [6432387, 13936178, ...]
 
// Convert entire palette to another colorspace
$hslPalette = $palette->toHsl();
$oklchPalette = $palette->toOklch();

// Chaining to convert to OKLCH colorspace and get CSS representation
$palette->toOklch()->toCss(); // ["oklch(0.4071 0.1471 310.39)", "oklch(0.7521 0.1381 87.12)", ...]
 
// map() and reduce() for custom processing
$hexList = $palette->map(fn ($color) => strtoupper($color->toHex('#')));
```

---

## Advanced Configuration

### Constructor options

All configuration is passed to the `ColorThief` constructor. Every parameter is optional and falls back to a sensible
default:

| Option             | Type                             | Default             | Description                                                                                                                                                                             |
|--------------------|----------------------------------|---------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `quality`          | `int`                            | `10`                | Pixel sampling rate. `1` analyzes every pixel (highest quality, slowest), `10` samples every 10th pixel.                                                                                |
| `whiteThreshold`   | `int`                            | `250`               | Pixels with R, G, and B all above this value are ignored. Set to `255` to disable white filtering                                                                                       |
| `alphaThreshold`   | `int`                            | `125`               | Pixels with alpha below this value are ignored (0 = fully transparent, 255 = fully opaque). Set to `0` to include transparent pixels                                                    |
| `minSaturation`    | `float`                          | `0`                 | Pixels with HSV saturation below this ratio (0–1) are ignored. Set to `0` to disable                                                                                                    |
| `colorSpace`       | `ColorSpace`                     | `ColorSpace::Oklch` | Color space used for internal quantization. `ColorSpace::Oklch` produces perceptually uniform palettes.                                                                                 |
| `preferredAdapter` | `AdapterInterface\|string\|null` | `null`              | Force using a specific image adapter: `'Gd'`, `'Imagick'`, `'Gmagick'`, or a custom `AdapterInterface` instance. Default `null` value means automatically choosing an available adapter |

### Overriding options per call with `with()`

`ColorThief` instances are **immutable**. If you need different settings for a specific call without affecting the base
configuration, use `with()` by calling it with named arguments. It accepts the exact same parameters as the constructor
and returns a new instance with
only the specified options overridden. The original instance is left unchanged.

```php
$thief = new ColorThief(quality: 10, minSaturation: 0.05);
 
// Faster extraction, keeping all other settings
$palette = $thief->with(quality: 50)->getPalette('/path/to/image.jpg');
 
// Stricter filtering for a single call
$color = $thief->with(whiteThreshold: 230, minSaturation: 0.1)->getColor('/path/to/image.jpg');
```

### Adjusting pixel filters

```php
// Include near-white pixels (useful for images where white is meaningful)
$thief = new ColorThief(whiteThreshold: 255);
 
// Exclude semi-transparent pixels more aggressively
$thief = new ColorThief(alphaThreshold: 200);
 
// Discard gray and low-saturation pixels to focus on vivid colors
$thief = new ColorThief(minSaturation: 0.15);
```

### Restricting extraction to a region

Use `ImageRegion` to analyze only a rectangular crop of the image:

```php
use ColorThief\ImageRegion;
 
// Crop starting at (50, 100), 200 px wide and 150 px tall
$region = new ImageRegion(x: 50, y: 100, width: 200, height: 150);
 
// Or: from (50, 100) to the right/bottom edges of the image
$region = new ImageRegion(x: 50, y: 100);
 
$color   = $thief->getColor('/path/to/image.jpg', region: $region);
$palette = $thief->getPalette('/path/to/image.jpg', region: $region);
```

---

## API Reference

### `ColorThief`

```php
new ColorThief(
    int $quality = 10,
    int $whiteThreshold = 250,
    int $alphaThreshold = 125,
    float $minSaturation = 0,
    ColorSpace $colorSpace = ColorSpace::Oklch,
    AdapterInterface|string|null $preferredAdapter = null,
)
```

#### Methods

**`getColor(mixed $sourceImage, ?ImageRegion $region = null): ?RgbColor`**
Returns the single most dominant color, or `null` if the image yields no usable pixels.

**`getPalette(mixed $sourceImage, int $colorCount = 10, ?ImageRegion $region = null): ColorPalette<RgbColor>`**
Returns a palette of dominant colors (2–20). Colors are ordered most to least dominant.

**`getSwatches(mixed $sourceImage, ?ImageRegion $region = null): ColorSwatches`**
Returns semantic swatches classified into six named roles: Vibrant, Muted, DarkVibrant, DarkMuted, LightVibrant, and
LightMuted.

**`with(...): self`**
Returns a new `ColorThief` instance with the specified options overridden. All arguments are optional and default to the
current instance's setting.

### `ColorPalette<TColor>`

An immutable, ordered, array-accessible collection of color objects.

| Method                           | Returns                    | Description                                                     |
|----------------------------------|----------------------------|-----------------------------------------------------------------|
| `count()`                        | `int`                      | Number of colors in the palette                                 |
| `isEmpty()`                      | `bool`                     | True if the palette has no colors                               |
| `toRgb()`                        | `ColorPalette<RgbColor>`   | Convert the palette to sRGB colorspace                          |
| `toOklch()`                      | `ColorPalette<OklchColor>` | Convert the palette to OKLCH colorspace                         |
| `toHsl()`                        | `ColorPalette<HslColor>`   | Convert the palette to HSL colorspace                           |
| `toHsv()`                        | `ColorPalette<HsvColor>`   | Convert the palette to HSV colorspace                           |
| `toCmyk()`                       | `ColorPalette<CmykColor>`  | Convert the palette to CMYK colorspace                          |
| `toArray()`                      | `list<array>`              | Component arrays in current colorspace                          |
| `toInt()`                        | `list<int>`                | Packed RGB integers                                             |
| `toHex(string $prefix = '')`     | `list<string>`             | Hex strings, e.g. `["#622a83", ...]`                            |
| `toCss()`                        | `list<string>`             | CSS strings using the colorspace's native notation              |
| `toString()`                     | `list<string>`             | String representation of each color in its current colorspace   |
| `map(callable $fn)`              | `array`                    | Apply a callback to each color                                  |
| `reduce(callable $fn, $initial)` | `mixed`                    | Reduce palette to a single value                                |

`ColorPalette` implements `ArrayAccess`, `Countable`, and `IteratorAggregate`, so it supports `foreach`, `count()`, and
`$palette[0]` access.

### Color classes

All color classes (`RgbColor`, `HslColor`, `HsvColor`, `OklchColor`, `CmykColor`) share these
members.

#### Population and weight

| Method         | Returns | Description                                                                      |
|----------------|---------|----------------------------------------------------------------------------------|
| `population()` | `int`   | Pixel count this color represents in the source image (among the sampled pixels) |
| `proportion()` | `float` | Fraction of analyzed pixels (0-1)                                                |

#### Conversions

| Method                       | Returns      | Description                                           |
|------------------------------|--------------|-------------------------------------------------------|
| `toRgb()`                    | `RgbColor`   | Convert to sRGB                                       |
| `toOklch()`                  | `OklchColor` | Convert to OKLCH                                      |
| `toHsl()`                    | `HslColor`   | Convert to HSL                                        |
| `toHsv()`                    | `HsvColor`   | Convert to HSV                                        |
| `toCmyk()`                   | `CmykColor`  | Convert to CMYK                                       |
| `toArray()`                  | `array`      | Components in native colorspace order                 |
| `toInt()`                    | `int`        | Packed `(r << 16 \| g << 8 \| b)` integer             |
| `toHex(string $prefix = '')` | `string`     | Hex string, e.g. `"622a83"` or `"#622a83"`            |
| `toCss()`                    | `string`     | CSS functional notation (`rgb()`, `hsl()`, `oklch()`) |
| `toString()`                 | `string`     | String representation in the current colorspace.      |
| `__toString()`               | `string`     | Same as `toString()`                                  |

#### Contrast and accessibility helpers

| Method        | Returns    | Description                                                                                          |
|---------------|------------|------------------------------------------------------------------------------------------------------|
| `luminance()` | `float`    | WCAG 2.x relative luminance (0 = black, 1 = white)                                                   |
| `isDark()`    | `bool`     | True when luminance ≤ 0.179                                                                          |
| `isLight()`   | `bool`     | True when luminance > 0.179                                                                          |
| `textColor()` | `RgbColor` | The recommended foreground text color (black or white) that gives better contrast on this background |

#### Channel accessors (specific per colorspace implementation)

##### `RgbColor`

| Method    | Returns | Description           |
|-----------|---------|-----------------------|
| `red()`   | `int`   | Red channel (0–255)   |
| `green()` | `int`   | Green channel (0–255) |
| `blue()`  | `int`   | Blue channel (0–255)  |

##### `HslColor`

| Method         | Returns | Description                  |
|----------------|---------|------------------------------|
| `hue()`        | `float` | Hue angle in degrees (0–360) |
| `saturation()` | `float` | Saturation (0–1)             |
| `lightness()`  | `float` | Lightness (0–1)              |

##### `HsvColor`

| Method         | Returns | Description                  |
|----------------|---------|------------------------------|
| `hue()`        | `float` | Hue angle in degrees (0–360) |
| `saturation()` | `float` | Saturation (0–1)             |
| `value()`      | `float` | Value / brightness (0–1)     |

##### `OklchColor`

| Method        | Returns | Description                    |
|---------------|---------|--------------------------------|
| `lightness()` | `float` | Lightness (0–1)                |
| `chroma()`    | `float` | Chroma / colorfulness (0–~0.4) |
| `hue()`       | `float` | Hue angle in degrees (0–360)   |

##### `CmykColor`

| Method      | Returns | Description             |
|-------------|---------|-------------------------|
| `cyan()`    | `float` | Cyan channel (0–1)      |
| `magenta()` | `float` | Magenta channel (0–1)   |
| `yellow()`  | `float` | Yellow channel (0–1)    |
| `black()`   | `float` | Key/Black channel (0–1) |

### `ColorSwatches`

Returned by `getSwatches()`. All properties are public and readonly.

| Property        | Type        | Description                            |
|-----------------|-------------|----------------------------------------|
| `$vibrant`      | `?RgbColor` | Bright, saturated, mid-lightness color |
| `$muted`        | `?RgbColor` | Desaturated, mid-lightness color       |
| `$darkVibrant`  | `?RgbColor` | Bright, saturated, dark color          |
| `$darkMuted`    | `?RgbColor` | Desaturated, dark color                |
| `$lightVibrant` | `?RgbColor` | Bright, saturated, light color         |
| `$lightMuted`   | `?RgbColor` | Desaturated, light color               |

Any property may be `null` if no palette color qualified for that role.

### `ImageRegion`

Defines a rectangular crop for color extraction.

```php
new ImageRegion(int $x = 0, int $y = 0, ?int $width = null, ?int $height = null)
```

All properties are public and readonly. `width` and `height` default to `null`, meaning the region extends to the
image's right and bottom edges respectively.

### Exceptions

All exceptions live under `ColorThief\Exception\` and extend a common base:

| Class                      | Thrown when                                                                                    |
|----------------------------|------------------------------------------------------------------------------------------------|
| `NotReadableException`     | The image source cannot be loaded (file not found, unsupported format, URL passed)             |
| `InvalidArgumentException` | A parameter is out of the allowed range (e.g. `colorCount` outside 2–20, region out of bounds) |
| `NotSupportedException`    | The requested operation is not supported by the active image adapter                           |
| `RuntimeException`         | An unexpected error occurs during image processing                                             |

---

## Migrating from v2.x to v3.x

ColorThief PHP v3.x brings major changes to the library's API, which has been thoroughly redesigned and modernized. Here's everything you need to perform the update.

### The class is now instantiated, not called statically

The most visible change: `ColorThief` is no longer a class with static methods. You instantiate it and call methods on
the instance.

```php
// Before (v2)
use ColorThief\ColorThief;
$color = ColorThief::getColor('/path/to/image.jpg');
$palette = ColorThief::getPalette('/path/to/image.jpg', 5);
 
// After (v3)
use ColorThief\ColorThief;
$thief = new ColorThief();
$color = $thief->getColor('/path/to/image.jpg');
$palette = $thief->getPalette('/path/to/image.jpg', colorCount: 5);
```

### Configuration is passed to the constructor, not as method arguments

In v2, options like quality and output format were passed as positional arguments to each method call. In v3, all
configuration lives on the `ColorThief` instance. Use `with()` to derive variants.

```php
// Before (v2)
$color   = ColorThief::getColor($image, 10, null, 'rgb');
$palette = ColorThief::getPalette($image, 5, 10, null, 'array');
 
// After (v3)
$thief   = new ColorThief(quality: 10);
$color   = $thief->getColor($image);        // returns RgbColor
$palette = $thief->getPalette($image, 5);   // returns ColorPalette<RgbColor>
```

### The `$format` parameter has been removed

v2's `getColor()` and `getPalette()` accepted a `$format` string (`'rgb'`, `'hex'`, `'int'`, `'array'`, `'obj'`) to
control the return type. In v3, methods always return typed color objects. Convert to any format you need using the
conversion methods.

```php
// Before (v2)
$hex     = ColorThief::getColor($image, 10, null, 'hex');  // "#622a83"
$arr     = ColorThief::getColor($image, 10, null, 'array'); // [98, 42, 131]
$int     = ColorThief::getColor($image, 10, null, 'int');   // 6432387
$palette = ColorThief::getPalette($image, 5, 10, null, 'hex'); // ["#622a83", ...]
 
// After (v3)
$thief   = new ColorThief();
$color   = $thief->getColor($image);
$hex     = $color->toHex('#');          // "#622a83"
$arr     = $color->toArray();           // [98, 42, 131]
$int     = $color->toInt();             // 6432387
$palette = $thief->getPalette($image, 5);
$hexList = $palette->toHex('#');        // ["#622a83", ...]
```

### RGB channel getters have been renamed

v2 exposed `getRed()`, `getGreen()`, and `getBlue()` on the color object. In v3 these are `red()`, `green()`, and
`blue()`.

```php
// Before (v2)
$color->getRed();
$color->getGreen();
$color->getBlue();
 
// After (v3)
$color->red();
$color->green();
$color->blue();
```

### URL loading has been removed

Passing a URL string to `getColor()` or `getPalette()` will now throw a `NotReadableException`. URL fetching was removed
to prevent Server-Side Request Forgery (SSRF) vulnerabilities.

Fetch the image yourself and pass the binary content:

```php
// Before (v2)
$color = ColorThief::getColor('https://example.com/image.jpg');
 
// After (v3)
// You are responsible for validating the URL and implementing
// appropriate security controls (allowlisting, redirect policy, timeouts, etc.)
$data  = file_get_contents('https://example.com/image.jpg');
$color = $thief->getColor($data);
```

### OKLCH is now the default quantization color space

v3 uses OKLCH internally by default, which produces more perceptually uniform palettes. If your application depends on
the exact palette output of v2, you can restore the old behavior:

```php
use ColorThief\ColorSpace;
 
$thief = new ColorThief(colorSpace: ColorSpace::Rgb);
```

### Maximum palette size reduced to 20

`getPalette()` now accepts a `colorCount` between **2 and 20** (v2 allowed up to 256). Requests above 20 will throw an
`InvalidArgumentException`.

### Edge-case behavior for degenerate images

In v2, fully transparent, fully white, or single-color images could throw exceptions. In v3 they return a graceful
result instead: a single-color palette containing black (transparent images), white (all-white images), or the single
color itself.

### Region parameter replaces positional `$area` array

The optional area/region parameter in v2 was an associative array. In v3 it is a typed `ImageRegion` object, passed as a
named argument.

```php
// Before (v2)
$palette = ColorThief::getPalette($image, 5, 10, ['x' => 50, 'y' => 100, 'w' => 200, 'h' => 150]);
 
// After (v3)
use ColorThief\ImageRegion;
 
$region  = new ImageRegion(x: 50, y: 100, width: 200, height: 150);
$palette = $thief->getPalette($image, colorCount: 5, region: $region);
```
 
---

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

## License

Licensed under the [MIT License](LICENSE).
