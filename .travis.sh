#!/bin/sh

# set -e

if [ "$TRAVIS_PHP_VERSION" = "7.0" ]; then
	git clone -b phpseven https://github.com/mkoppanen/imagick.git
	cd imagick
	phpize
	./configure --prefix=$HOME/.phpenv/versions/$(phpenv version-name)
	make
	make install
	echo "extension = imagick.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
	cd ..
elif [ "$TRAVIS_PHP_VERSION" != "hhvm" ]; then
	printf "\n" | pecl install imagick
fi

