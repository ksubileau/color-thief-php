#!/usr/bin/env php
<?php
/**
 * Generates a realistic synthetic 3840×2400 JPEG test image using GD.
 * Simulates a natural scene (sky gradient + ground + noise + coloured shapes).
 *
 * Usage: php generate_image.php <output_path>
 */
declare(strict_types=1);
$output = $argv[1] ?? '/repo/tests/images/child_painter_3840x2400.jpg';
if (!extension_loaded('gd')) {
    fwrite(STDERR, "GD extension required.\n");
    exit(1);
}
$w = 3840;
$h = 2400;
$img = imagecreatetruecolor($w, $h);
// Sky gradient (top 40 %)
for ($y = 0; $y < (int) ($h * 0.4); $y++) {
    $ratio = $y / ($h * 0.4);
    $c = imagecolorallocate($img,
        (int) (100 + 120 * $ratio),
        (int) (150 +  80 * $ratio),
        (int) (220 -  60 * $ratio)
    );
    imageline($img, 0, $y, $w - 1, $y, $c);
}
// Ground gradient (bottom 60 %)
for ($y = (int) ($h * 0.4); $y < $h; $y++) {
    $ratio = ($y - $h * 0.4) / ($h * 0.6);
    $c = imagecolorallocate($img,
        (int) (30 +  80 * $ratio),
        (int) (100 + 60 * (1 - $ratio)),
        (int) (20 +  40 * $ratio)
    );
    imageline($img, 0, $y, $w - 1, $y, $c);
}
// Noise layer
mt_srand(42);
for ($i = 0; $i < 500000; $i++) {
    $x     = mt_rand(0, $w - 1);
    $y     = mt_rand(0, $h - 1);
    $noise = mt_rand(-30, 30);
    $rgb   = imagecolorat($img, $x, $y);
    imagesetpixel($img, $x, $y, imagecolorallocate($img,
        max(0, min(255, (($rgb >> 16) & 0xFF) + $noise)),
        max(0, min(255, (($rgb >>  8) & 0xFF) + $noise)),
        max(0, min(255, ( $rgb        & 0xFF) + $noise))
    ));
}
// Coloured ellipses (simulate flowers / objects)
for ($i = 0; $i < 80; $i++) {
    $c = imagecolorallocate($img, mt_rand(100, 255), mt_rand(50, 200), mt_rand(20, 150));
    imagefilledellipse($img,
        mt_rand(0, $w),
        mt_rand((int) ($h * 0.4), $h),
        mt_rand(20, 200),
        mt_rand(20, 150),
        $c
    );
}
@mkdir(dirname($output), 0755, true);
imagejpeg($img, $output, 90);
imagedestroy($img);
$size = filesize($output);
fwrite(STDOUT, "Generated $output (" . round($size / 1024 / 1024, 2) . " MB)\n");
