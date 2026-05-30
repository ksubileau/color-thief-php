#!/usr/bin/env bash
# docker-entrypoint.sh
#
# Usage inside container:
#   docker run --rm colorthief-bench \
#       --base  <commit-sha>          (required) branch start commit
#       --head  <branch-or-sha>       (required) tip to benchmark up to
#       [--repo-url  <git-url>]       clone URL  (default: GitHub repo)
#       [--iterations <n>]            getPalette calls per commit (default 50)
#       [--threshold  <f>]            significance threshold      (default 0.10)
#
set -euo pipefail
REPO_URL="https://github.com/ksubileau/color-thief-php.git"
BASE=""
HEAD_REF=""
ITERATIONS=50
THRESHOLD=0.10
# ---- Parse args ----
while [[ $# -gt 0 ]]; do
    case "$1" in
        --base)       BASE="$2";        shift 2 ;;
        --head)       HEAD_REF="$2";    shift 2 ;;
        --repo-url)   REPO_URL="$2";    shift 2 ;;
        --iterations) ITERATIONS="$2";  shift 2 ;;
        --threshold)  THRESHOLD="$2";   shift 2 ;;
        *) echo "Unknown argument: $1" >&2; exit 1 ;;
    esac
done
if [[ -z "$BASE" || -z "$HEAD_REF" ]]; then
    echo "Usage: docker run --rm colorthief-bench --base <sha> --head <ref>" >&2
    echo "  Optional: --repo-url <url>  --iterations <n>  --threshold <f>" >&2
    exit 1
fi
REPO=/repo
IMAGE_PATH="$REPO/tests/images/child_painter_3840x2400.jpg"
CACHE=/tmp/perf_results.json
echo "================================================================"
echo "  ColorThief PHP — getPalette() dichotomic performance benchmark"
echo "================================================================"
echo "  Repo URL   : $REPO_URL"
echo "  Base       : $BASE"
echo "  Head       : $HEAD_REF"
echo "  Iterations : $ITERATIONS"
echo "  Threshold  : $THRESHOLD"
echo ""
# ---- 1. Clone repository ----
echo ">> Cloning repository …"
git clone --no-local "$REPO_URL" "$REPO" 2>&1
# Fetch the requested head ref in case it's a remote branch
git -C "$REPO" fetch --all --quiet 2>&1 || true
# ---- 2. Generate test image ----
echo ">> Generating test image (3840×2400) …"
php -d xdebug.mode=off /bench/generate_image.php "$IMAGE_PATH"
# ---- 3. Make run_perf.php reachable relative to /bench ----
# dichotomic_bench.py expects run_perf.php next to itself (already in /bench)
# ---- 4. Run dichotomic benchmark ----
echo ">> Starting benchmark …"
echo ""
python3 /bench/dichotomic_bench.py \
    --repo       "$REPO"       \
    --base       "$BASE"       \
    --head       "$HEAD_REF"   \
    --iterations "$ITERATIONS" \
    --threshold  "$THRESHOLD"  \
    --cache      "$CACHE"
