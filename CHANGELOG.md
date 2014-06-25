# Color Thief PHP Changelog

## `1.1.0`

 * Add support for Imagick and GD resources. In addition to the path or URL of the image, now you can also directly pass the GD resource or Imagick instance to the getColor and getPalette methods.
 * Fix fatal error whith solid white images. An exception is now thrown in this case, allowing the caller to catch it.
 * Fix possible undefined offset under certain circumstances
 * Change error handling policy : throw exceptions in case of errors instead of return false.

## `1.0.0`

 * Initial release
