# ColorThief PHP — Performance Benchmark
Scripts and `Dockerfile.perf` for dichotomic performance profiling of
`ColorThief::getPalette()` across git commits.
## Quick start (Docker)
```bash
# Build
docker build -f Dockerfile.perf -t colorthief-bench .
# Run  (replace refs as needed)
docker run --rm colorthief-bench \
    --base d6189b34 \
    --head origin/main
```
### Optional runtime flags
| Flag | Default | Description |
|---|---|---|
| `--base <sha>` | — | **Required.** Branch start commit SHA |
| `--head <ref>` | — | **Required.** Head branch / tag / SHA to benchmark up to |
| `--repo-url <url>` | GitHub repo | Git URL to clone |
| `--iterations <n>` | `50` | `getPalette` calls per commit |
| `--threshold <f>` | `0.10` | Significance threshold (10 %) |
## How it works
1. The container clones the repository at runtime.
2. A synthetic 3840×2400 JPEG is generated with the GD extension to reproduce a realistic colour scene.
3. `dichotomic_bench.py` benchmarks the base and head commits.
    - If the overall change exceeds the threshold, it bisects the range and repeats.
    - Additionally it always tests commits whose messages hint at algorithmic changes
      (quantisation loop, SplFixedArray removal, rector refactoring, VBox refactor …).
4. A full report with per-commit timings and a summary is printed to stdout.
## Running without Docker
```bash
# Install deps
composer install --no-dev
pip3 install -r requirements.txt   # no extra deps; stdlib only
# Generate image
php -d xdebug.mode=off perf/generate_image.php tests/images/child_painter_3840x2400.jpg
# Run benchmark
python3 perf/dichotomic_bench.py \
    --repo . \
    --base d6189b34 \
    --head HEAD
```
## Files
| File | Purpose |
|---|---|
| `perf/dichotomic_bench.py` | Main orchestrator — bisect logic + report |
| `perf/run_perf.php` | PHP perf script called per commit |
| `perf/generate_image.php` | Generates the synthetic 3840×2400 test image |
| `perf/docker-entrypoint.sh` | Container entrypoint — parses args, clones repo, runs bench |
| `Dockerfile.perf` | Docker image definition |
