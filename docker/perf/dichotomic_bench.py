#!/usr/bin/env python3
"""
dichotomic_bench.py — Dichotomic performance benchmark for ColorThief::getPalette.
Walks the commits between BASE_COMMIT and HEAD_REF using a binary-search strategy:
  1. Bench the base commit and the head commit.
  2. If the change is ≥ THRESHOLD (default 10 %), bench the midpoint.
  3. Recurse on the half that shows the significant change.
  4. Also bench hand-picked performance-relevant commits (SplFixedArray removal,
     double-increment fix, rector refactor, etc.).
  5. Print a full report to stdout.
Usage:
    python3 dichotomic_bench.py \\
        --repo   /path/to/color-thief-php  \\
        --base   <commit-sha>              \\
        --head   <branch-or-sha>           \\
        [--iterations 50]                  \\
        [--threshold  0.10]                \\
        [--cache /tmp/perf_results.json]
"""
import argparse
import json
import os
import subprocess
import sys
from datetime import datetime
# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------
def git(repo: str, *args) -> str:
    return subprocess.check_output(["git", "-C", repo, *args], text=True).strip()
def commit_info(repo: str, sha: str) -> tuple[str, str]:
    raw = git(repo, "log", "--format=%H|%s", "-1", sha)
    parts = raw.split("|", 1)
    return parts[0][:8], (parts[1] if len(parts) > 1 else "?")
def install_deps(repo: str) -> None:
    for cmd in (
            ["composer", "install", "--no-interaction", "--no-plugins",
             "--no-scripts", "--no-dev", "-q"],
            ["composer", "install", "--no-interaction", "--no-scripts", "--no-dev", "-q"],
    ):
        r = subprocess.run(cmd, cwd=repo, capture_output=True)
        if r.returncode == 0:
            return
    raise RuntimeError("composer install failed")
def run_bench(repo: str, iterations: int) -> dict:
    image = os.path.join(repo, "tests", "images", "child_painter_3840x2400.jpg")
    if not os.path.exists(image):
        raise FileNotFoundError(f"Test image not found: {image}")
    script = os.path.join(os.path.dirname(__file__), "run_perf.php")
    result = subprocess.run(
        ["php", "-d", "xdebug.mode=off", script, image, str(iterations)],
        capture_output=True, text=True, timeout=600,
    )
    if result.returncode != 0:
        err = (result.stderr or "").strip()
        out = (result.stdout or "").strip()
        details = err if err else (out if out else f"(no output, exit code {result.returncode})")
        raise RuntimeError(f"Perf script failed:\n{details[-1000:]}")
    return json.loads(result.stdout.strip())
# ---------------------------------------------------------------------------
# Benchmark one commit (checkout → deps → bench)
# ---------------------------------------------------------------------------
def bench_commit(repo: str, sha: str, idx: int, total: int,
                 iterations: int, cache: dict) -> dict | None:
    short, msg = commit_info(repo, sha)
    if short in cache:
        print(f"  [CACHED] [{idx:2d}/{total}] {short}: {msg}")
        print(f"           avg={cache[short]['avg_time_ms']:.1f} ms  "
              f"mem={cache[short]['peak_memory_mb']:.1f} MB")
        sys.stdout.flush()
        return cache[short]
    print(f"\n{'=' * 70}")
    print(f"  [{idx:2d}/{total}] {short}: {msg}")
    print(f"  Started: {datetime.now().strftime('%H:%M:%S')}  "
          f"({iterations} iterations, ~2 min) …")
    sys.stdout.flush()
    # Preserve test image (not tracked by git)
    image_path = os.path.join(repo, "tests", "images", "child_painter_3840x2400.jpg")
    image_backup = None
    if os.path.exists(image_path):
        import shutil, tempfile
        tmp = tempfile.NamedTemporaryFile(suffix=".jpg", delete=False)
        shutil.copy2(image_path, tmp.name)
        image_backup = tmp.name
    try:
        subprocess.run(["git", "-C", repo, "stash", "--quiet"],
                       capture_output=True)
        subprocess.run(["git", "-C", repo, "checkout", sha, "--quiet"],
                       capture_output=True, check=True)
    except subprocess.CalledProcessError as e:
        print(f"  ERROR checkout: {e}")
        return None
    finally:
        if image_backup:
            os.makedirs(os.path.dirname(image_path), exist_ok=True)
            import shutil
            shutil.copy2(image_backup, image_path)
            os.unlink(image_backup)
    try:
        t0 = datetime.now()
        install_deps(repo)
        data = run_bench(repo, iterations)
        elapsed = (datetime.now() - t0).total_seconds()
    except Exception as e:
        print(f"  ERROR: {e}")
        return None
    data.update({"sha": short, "idx": idx, "message": msg})
    cache[short] = data
    print(f"  Done in {elapsed:.0f}s: avg={data['avg_time_ms']:.1f} ms  "
          f"mem={data['peak_memory_mb']:.1f} MB")
    sys.stdout.flush()
    return data
# ---------------------------------------------------------------------------
# Dichotomic bisect
# ---------------------------------------------------------------------------
def is_significant(a: dict, b: dict, threshold: float) -> bool:
    return abs((b["avg_time_ms"] - a["avg_time_ms"]) / a["avg_time_ms"]) > threshold
def pct(a: dict, b: dict, key: str = "avg_time_ms") -> float:
    return (b[key] - a[key]) / a[key] * 100
def bisect(repo: str, commits: list[str], left: int, right: int,
           threshold: float, iterations: int, cache: dict,
           checked: set) -> None:
    """Recursively bisect the [left, right] segment."""
    if right - left <= 1 or (left, right) in checked:
        return
    checked.add((left, right))
    mid = (left + right) // 2
    mid_res = bench_commit(repo, commits[mid], mid, len(commits) - 1,
                           iterations, cache)
    if mid_res is None:
        return
    left_res  = cache[commits[left][:8]]
    right_res = cache[commits[right][:8]]
    l2m = pct(left_res, mid_res)
    m2r = pct(mid_res, right_res)
    print(f"  Segment [{left}..{mid}]: {l2m:+.1f}%  |  [{mid}..{right}]: {m2r:+.1f}%")
    sys.stdout.flush()
    if is_significant(left_res, mid_res, threshold):
        bisect(repo, commits, left, mid, threshold, iterations, cache, checked)
    if is_significant(mid_res, right_res, threshold):
        bisect(repo, commits, mid, right, threshold, iterations, cache, checked)
# ---------------------------------------------------------------------------
# Report
# ---------------------------------------------------------------------------
def print_report(repo: str, commits: list[str], cache: dict,
                 threshold: float) -> None:
    tested = []
    for idx, sha in enumerate(commits):
        short = sha[:8]
        if short in cache:
            r = dict(cache[short])
            r["idx"] = idx
            tested.append(r)
    tested.sort(key=lambda r: r["idx"])
    now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    w = 108
    print("\n" + "=" * w)
    print("  COLOR-THIEF PHP — getPalette() PERFORMANCE BENCHMARK REPORT")
    print("=" * w)
    print(f"  Repository     : {repo}")
    print(f"  Base commit    : {commits[0][:8]}")
    print(f"  Head commit    : {commits[-1][:8]}")
    print(f"  Branch commits : {len(commits) - 1}")
    print(f"  Commits tested : {len(tested)}")
    print(f"  Threshold      : ±{threshold * 100:.0f}%")
    print(f"  Iterations/run : 50 × ColorThief::getPalette($path, 10, 10, null)")
    print(f"  Test image     : tests/images/child_painter_3840x2400.jpg (3840×2400 JPEG)")
    print(f"  Generated      : {now}")
    print()
    # ---- Detail table ----
    print("DETAILED RESULTS")
    print("-" * w)
    hdr = f"{'Idx':<5}{'Commit':<10}{'Avg (ms)':>10}  {'Δ base':>8}  {'Δ prev':>8}  {'Mem (MB)':>9}  Message"
    print(hdr)
    print("-" * w)
    base_time = tested[0]["avg_time_ms"] if tested else 1.0
    prev_time: float | None = None
    prev_idx: int | None = None
    for r in tested:
        vs_base = (r["avg_time_ms"] - base_time) / base_time * 100
        vs_prev = (r["avg_time_ms"] - prev_time) / prev_time * 100 if prev_time else 0.0
        gap = f" (skipping {r['idx'] - prev_idx - 1})" if prev_idx is not None and r["idx"] - prev_idx > 1 else ""
        flag = ""
        if prev_time and abs(vs_prev) > threshold * 100:
            flag = " ⚠  DEGRADATION" if vs_prev > 0 else " ✅ IMPROVEMENT"
        msg = r.get("message", "?")[:48]
        print(
            f"[{r['idx']:2d}]  {r['sha']:<10}"
            f"{r['avg_time_ms']:>9.1f}ms"
            f"  {vs_base:>+7.1f}%"
            f"  {vs_prev:>+7.1f}%"
            f"  {r['peak_memory_mb']:>7.1f} MB"
            f"  {msg}{flag}{gap}"
        )
        prev_time = r["avg_time_ms"]
        prev_idx  = r["idx"]
    # ---- Summary ----
    print()
    print("=" * w)
    print("SUMMARY")
    print("-" * w)
    overall_pct = (tested[-1]["avg_time_ms"] - tested[0]["avg_time_ms"]) / tested[0]["avg_time_ms"] * 100
    verdict = "no significant overall degradation" if abs(overall_pct) < threshold * 100 \
        else ("DEGRADATION ⚠" if overall_pct > 0 else "IMPROVEMENT ✅")
    print(f"  Base  [{tested[0]['idx']:2d}] {tested[0]['sha']}: {tested[0]['avg_time_ms']:.1f} ms")
    print(f"  Head  [{tested[-1]['idx']:2d}] {tested[-1]['sha']}: {tested[-1]['avg_time_ms']:.1f} ms")
    print(f"  Overall change : {overall_pct:+.1f}%  → {verdict}")
    print()
    print("  Significant transitions (>10% between consecutive tested commits):")
    prev_time = None
    prev_idx  = None
    found = False
    for r in tested:
        if prev_time and abs((r["avg_time_ms"] - prev_time) / prev_time) > threshold:
            direction = "DEGRADATION ⚠" if r["avg_time_ms"] > prev_time else "IMPROVEMENT ✅"
            change_pct = (r["avg_time_ms"] - prev_time) / prev_time * 100
            span = commits[prev_idx + 1:r["idx"] + 1]
            print(f"\n    {direction}  {change_pct:+.1f}%"
                  f"  between tested commits [{prev_idx}..{r['idx']}]:")
            for c in span:
                _, m = commit_info(repo, c)
                marker = "  ← tested" if c[:8] in cache else ""
                print(f"      [{commits.index(c):2d}] {c[:8]}  {m}{marker}")
            found = True
        prev_time = r["avg_time_ms"]
        prev_idx  = r["idx"]
    if not found:
        print("    None — all consecutive tested commits are within the ±10% threshold.")
    print("\n" + "=" * w)
# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------
def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--repo",       default="/repo",
                        help="Path to the cloned repository")
    parser.add_argument("--base",       required=True,
                        help="Base commit SHA (branch start)")
    parser.add_argument("--head",       default="HEAD",
                        help="Head ref / branch / SHA to benchmark up to")
    parser.add_argument("--iterations", type=int, default=50,
                        help="getPalette calls per commit (default: 50)")
    parser.add_argument("--threshold",  type=float, default=0.10,
                        help="Significance threshold (default: 0.10 = 10%%)")
    parser.add_argument("--cache",      default="/tmp/perf_results.json",
                        help="JSON file to cache results across runs")
    args = parser.parse_args()
    repo       = args.repo
    threshold  = args.threshold
    iterations = args.iterations
    # Build ordered commit list: index 0 = base, index N = head.
    # Two-dot notation (base..head) lists commits reachable from head but not
    # from base, which is exactly the set of new commits we want to benchmark.
    # IMPORTANT: use --format=%H to get FULL SHAs. Abbreviated SHAs (e.g. from
    # --oneline, which defaults to 7 chars) would not match the 8-char cache
    # keys derived from full SHAs in bench_commit(), causing print_report() to
    # silently skip every commit except the base.
    raw = git(repo, "log", "--format=%H", f"{args.base}..{args.head}")
    if not raw:
        sys.exit(
            f"No commits found between {args.base} and {args.head}.\n"
            "  • Make sure --base is an ancestor of --head.\n"
            "  • Do not pass the bare symbolic ref 'HEAD' for --base: after a fresh\n"
            "    clone it resolves to the default branch (same as origin/main).\n"
            "    Use an explicit remote ref instead, e.g.:\n"
            "      --base origin/3.x-dev --head origin/main"
        )
    branch_commits = raw.strip().splitlines()
    branch_commits.reverse()                     # oldest first
    # Normalise the base ref to a full SHA so all entries in `commits` are
    # comparable and produce identical 8-char cache keys.
    base_sha = git(repo, "rev-parse", "--verify", args.base)
    commits = [base_sha] + branch_commits        # prepend base
    print(f"Commits on branch : {len(commits) - 1}  "
          f"(base: {commits[0][:8]} → head: {commits[-1][:8]})")
    print(f"Threshold         : ±{threshold * 100:.0f}%")
    sys.stdout.flush()
    # Load / init cache
    cache: dict = {}
    if os.path.exists(args.cache):
        with open(args.cache) as f:
            cache = json.load(f)
    def save():
        with open(args.cache, "w") as f:
            json.dump(cache, f, indent=2)
    # --- Step 1: bench base and head ---
    base_res = bench_commit(repo, commits[0],  0, len(commits) - 1, iterations, cache)
    save()
    head_res = bench_commit(repo, commits[-1], len(commits) - 1, len(commits) - 1,
                            iterations, cache)
    save()
    if base_res is None or head_res is None:
        sys.exit("ERROR: could not bench base or head commit.")
    overall = pct(base_res, head_res)
    print(f"\nBase→Head change: {overall:+.1f}% time, "
          f"{pct(base_res, head_res, 'peak_memory_mb'):+.1f}% memory")
    # --- Step 2: dichotomic bisect if significant ---
    checked: set = set()
    if is_significant(base_res, head_res, threshold):
        bisect(repo, commits, 0, len(commits) - 1, threshold, iterations, cache, checked)
        save()
    # --- Step 3: always bench performance-relevant commits ---
    # These commit messages hint at algorithmic changes worth inspecting regardless
    # of whether the dichotomic pass already covered them.
    PERF_KEYWORDS = (
        "splfix", "double increment", "quantiz", "refactor", "rector",
        "vbox", "spl", "pixel", "typed propert",
    )
    extra_indices = []
    for i, sha in enumerate(commits):
        _, msg = commit_info(repo, sha)
        if any(kw in msg.lower() for kw in PERF_KEYWORDS):
            extra_indices.append(i)
    # Also add evenly spaced midpoints for a smoother curve
    step = max(1, len(commits) // 6)
    extra_indices += list(range(0, len(commits), step))
    for idx in sorted(set(extra_indices)):
        r = bench_commit(repo, commits[idx], idx, len(commits) - 1, iterations, cache)
        if r:
            save()
    # --- Step 4: restore HEAD ---
    subprocess.run(["git", "-C", repo, "checkout", args.head, "--quiet"],
                   capture_output=True)
    # --- Step 5: print report ---
    print_report(repo, commits, cache, threshold)
if __name__ == "__main__":
    main()
