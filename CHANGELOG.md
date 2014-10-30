# Color Thief PHP Changelog

## `1.2.0`

 * Add support of area targeting (see #12).
 * Fix error with remote images (see #13, thank @rewmike).
 * Fix minor syntax errors (see #14, thank @grachov).
 * Small performance improvements and code cleanup.

## `1.1.0`

 * Add support for Imagick and GD resources. In addition to the path or URL of the image, now you can also directly pass the GD resource or Imagick instance to the getColor and getPalette methods  (see #10).
 * Fix fatal error whith solid white images. An exception is now thrown in this case, allowing the caller to catch it (see #11).
 * Fix possible undefined offset under certain circumstances.
 * Change error handling policy : throw exceptions in case of errors instead of return false.

## `1.0.0`

 * Initial release
