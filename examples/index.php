<?php

require_once __DIR__.'/../vendor/autoload.php';

use ColorThief\ColorThief;

$dominantColor = ColorThief::getPalette('test.jpg',5);

print_r($dominantColor);