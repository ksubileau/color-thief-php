#!/usr/bin/env bash
# docker-entrypoint.sh
#
# Usage inside container:
#   docker run --rm colorthief-bench \
#       --base  <remote-ref-or-sha>   (required) base commit / branch  e.g. origin/3.x-dev
#       --head  <remote-ref-or-sha>   (required) head commit / branch  e.g. origin/main
#       [--repo-url  <git-url>]       clone URL  (default: GitHub repo)
#       [--iterations <n>]            getPalette calls per commit (default 50)
#       [--threshold  <f>]            significance threshold      (default 0.10)
#
# NOTE: Do NOT pass the bare symbolic ref "HEAD" for --base.  After a fresh
#       clone, HEAD always points to the repository's default branch, which is
#       almost certainly the same commit as origin/main, giving zero commits to
#       benchmark.  Pass an explicit remote ref instead, e.g.:
#         --base origin/3.x-dev --head origin/main
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
# Fetch all remote refs so that branch names like origin/3.x-dev are resolvable
git -C "$REPO" fetch --all --quiet 2>&1 || true

# ---- 1b. Resolve refs to full SHAs and validate ----
BASE_SHA=$(git -C "$REPO" rev-parse --verify "$BASE" 2>/dev/null || true)
HEAD_SHA=$(git -C "$REPO" rev-parse --verify "$HEAD_REF" 2>/dev/null || true)

if [[ -z "$BASE_SHA" ]]; then
    echo "ERROR: --base '$BASE' could not be resolved in the cloned repository." >&2
    echo "       Use a remote ref such as 'origin/3.x-dev' or an explicit commit SHA." >&2
    exit 1
fi
if [[ -z "$HEAD_SHA" ]]; then
    echo "ERROR: --head '$HEAD_REF' could not be resolved in the cloned repository." >&2
    echo "       Use a remote ref such as 'origin/main' or an explicit commit SHA." >&2
    exit 1
fi
if [[ "$BASE_SHA" == "$HEAD_SHA" ]]; then
    echo "ERROR: --base and --head resolve to the same commit ($BASE_SHA)." >&2
    echo "       There are no commits to benchmark between them." >&2
    if [[ "$BASE" == "HEAD" ]]; then
        echo "" >&2
        echo "       'HEAD' in the container points to the cloned repository's default" >&2
        echo "       branch, which is the same as origin/main." >&2
        echo "       Pass the remote ref of your development branch instead, e.g.:" >&2
        echo "         --base origin/3.x-dev --head origin/main" >&2
    fi
    exit 1
fi

# Replace symbolic/branch refs with resolved SHAs for deterministic benchmarking
BASE="$BASE_SHA"
HEAD_REF="$HEAD_SHA"

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
