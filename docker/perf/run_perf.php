<?php
// Performance test script for ColorThief::getPalette
// Usage: php run_perf.php <path_to_repo> <iterations>

require_once __DIR__.'/../vendor/autoload.php';

if ($argc !== 2) {
    fwrite(STDERR, "Usage: php run_perf.php <image_path>\n");
    exit(1);
}

$imagePath = $argv[1];
$iterations = (int)($argv[2] ?? 50);

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
