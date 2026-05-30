<?php
// Performance test script for ColorThief::getPalette
// Usage: php run_perf.php <image_path> [iterations]

if ($argc < 2 || $argc > 3) {
    fwrite(STDERR, "Usage: php run_perf.php <image_path> [iterations]\n");
    exit(1);
}

$imagePath = $argv[1];
$iterations = (int)($argv[2] ?? 50);

// Locate the autoloader of the repository under test.
// The script is copied to /bench/run_perf.php inside the benchmark Docker image,
// while Composer dependencies are installed in the cloned repository (e.g. /repo),
// so __DIR__/../vendor does NOT work. Resolve the autoloader from candidate
// locations, including the repo derived from the image path
// (<repo>/tests/images/<file>).
$autoloadCandidates = [
    dirname($imagePath, 3).'/vendor/autoload.php', // <repo>/vendor/autoload.php
    __DIR__.'/../../vendor/autoload.php',          // repo checkout: docker/perf/run_perf.php
    __DIR__.'/../vendor/autoload.php',             // legacy layout
];
$autoload = null;
foreach ($autoloadCandidates as $candidate) {
    if (is_file($candidate)) {
        $autoload = $candidate;
        break;
    }
}
if ($autoload === null) {
    fwrite(STDERR, "Composer autoloader not found. Tried:\n  - ".implode("\n  - ", $autoloadCandidates)."\n");
    exit(1);
}
require_once $autoload;

use ColorThief\ColorThief;

if (!file_exists($imagePath)) {
    fwrite(STDERR, "Image not found: $imagePath\n");
    exit(1);
}

// Warmup: 1 run
try {
    ColorThief::getPalette($imagePath, 10, 10, null);
} catch (Throwable $e) {
    fwrite(STDERR, "Error during warmup: " . $e->getMessage() . "\n");
    exit(1);
}

$memBefore = memory_get_usage(true);
$start = microtime(true);

for ($i = 0; $i < $iterations; $i++) {
    try {
        ColorThief::getPalette($imagePath, 10, 10, null);
    } catch (Throwable $e) {
        fwrite(STDERR, "Error during iteration $i: " . $e->getMessage() . "\n");
        exit(1);
    }
}

$elapsed = microtime(true) - $start;
$memAfter = memory_get_peak_usage(true);

$avgTime = $elapsed / $iterations;
$memUsage = $memAfter - $memBefore;

// Output as JSON
echo json_encode([
    'iterations' => $iterations,
    'total_time_s' => round($elapsed, 4),
    'avg_time_ms' => round($avgTime * 1000, 4),
    'peak_memory_bytes' => $memAfter,
    'memory_delta_bytes' => $memUsage,
    'peak_memory_mb' => round($memAfter / 1024 / 1024, 2),
]);
echo "\n";
