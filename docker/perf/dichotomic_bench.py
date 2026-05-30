#!/usr/bin/env python3
"""
dichotomic_bench.py — Dichotomic performance benchmark for ColorThief::getPalette.

Goal
----
Detect whether there is a statistically significant performance change between
a BASE and a HEAD commit on the same branch and, if so, identify which commit
(or small set of commits) in the history is responsible.

Strategy
--------
  1. Benchmark BASE and HEAD with multiple iterations and capture per-iteration
     samples (time + memory). Use the MEDIAN as the comparison reference and a
     Welch t-test on the raw samples to decide whether a delta is significant
     (instead of a fixed arbitrary % threshold).
  2. If a significant change exists, bisect the commit range to locate it.
     Commits that change neither PHP nor composer files are skipped (they
     cannot affect runtime behaviour).
  3. Always benchmark a handful of hand-picked performance-relevant commits
     (SplFixedArray removal, double-increment fix, rector refactor, etc.).
  4. Produce a detailed report including dispersion (min/median/p95/stdev) for
     both time and memory.

Usage
-----
    python3 dichotomic_bench.py \\
        --repo   /path/to/color-thief-php  \\
        --base   <commit-sha>              \\
        --head   <branch-or-sha>           \\
        --image  /tmp/bench_image.jpg      \\
        [--iterations 50]                  \\
        [--threshold  0.05]                \\
        [--cache /tmp/perf_results.json]
"""
import argparse
import json
import math
import os
import statistics
import subprocess
import sys
from datetime import datetime


# ---------------------------------------------------------------------------
# Generic helpers
# ---------------------------------------------------------------------------
def git(repo: str, *args) -> str:
    return subprocess.check_output(["git", "-C", repo, *args], text=True).strip()


def commit_info(repo: str, sha: str) -> tuple[str, str]:
    raw = git(repo, "log", "--format=%H|%s", "-1", sha)
    parts = raw.split("|", 1)
    return parts[0][:8], (parts[1] if len(parts) > 1 else "?")


def commit_touches_runtime(repo: str, sha: str) -> bool:
    """Return True if the commit changes at least one file that can affect
    runtime behaviour: any .php file or composer.json/composer.lock.
    Merge commits and commits without a parent are conservatively considered
    relevant (we cannot easily diff them)."""
    try:
        raw = git(repo, "show", "--name-only", "--format=", sha)
    except subprocess.CalledProcessError:
        return True
    files = [f for f in raw.splitlines() if f.strip()]
    if not files:
        return True  # empty diff (merge, etc.) — be conservative
    for f in files:
        if f.endswith(".php") or os.path.basename(f) in ("composer.json", "composer.lock"):
            return True
    return False


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


def run_bench(repo: str, image_path: str, iterations: int) -> dict:
    if not os.path.exists(image_path):
        raise FileNotFoundError(f"Test image not found: {image_path}")
    script = os.path.join(os.path.dirname(__file__), "run_perf.php")
    env = os.environ.copy()
    # Tell run_perf.php exactly where the autoloader of the commit under test
    # lives. The test image is outside the repo, so it can no longer be used
    # to infer the repo path on the PHP side.
    env["BENCH_AUTOLOAD"] = os.path.join(repo, "vendor", "autoload.php")
    result = subprocess.run(
        ["php", "-d", "xdebug.mode=off", script, image_path, str(iterations)],
        capture_output=True, text=True, timeout=600, env=env,
    )
    if result.returncode != 0:
        err = (result.stderr or "").strip()
        out = (result.stdout or "").strip()
        details = err if err else (out if out else f"(no output, exit code {result.returncode})")
        raise RuntimeError(f"Perf script failed:\n{details[-1000:]}")
    data = json.loads(result.stdout.strip())
    if data.get("_schema") != 2:
        raise RuntimeError(
            f"run_perf.php returned an unexpected schema (got {data.get('_schema')!r}, expected 2)"
        )
    return data

# ---------------------------------------------------------------------------
# Benchmark one commit (checkout → deps → bench)
# ---------------------------------------------------------------------------
SKIPPED_MARKER = "__skipped_no_runtime_changes__"


def bench_commit(repo: str, sha: str, idx: int, total: int,
                 iterations: int, image_path: str, cache: dict,
                 allow_skip: bool = True) -> dict | None:
    """Benchmark a single commit. Returns the result dict (with samples and
    stats) or None on error. Returns a sentinel dict with key
    ``skipped == True`` if the commit only changes non-PHP files."""
    short, msg = commit_info(repo, sha)
    if short in cache:
        cached = cache[short]
        if cached.get("skipped"):
            print(f"  [CACHED skipped] [{idx:2d}/{total}] {short}: {msg}  (no PHP/composer changes)")
        else:
            t = cached["time_ms"]
            m = cached["memory_bytes"]
            print(f"  [CACHED]         [{idx:2d}/{total}] {short}: {msg}")
            print(f"                   time median={t['median']:.1f} ms ±{t['stdev']:.1f}  "
                  f"mem median={m['median']/1024/1024:.2f} MB ±{m['stdev']/1024/1024:.2f}")
        sys.stdout.flush()
        return cached

    if allow_skip and not commit_touches_runtime(repo, sha):
        print(f"\n  [{idx:2d}/{total}] {short}: {msg}")
        print(f"  SKIPPED — no .php or composer.* changes (cannot affect runtime).")
        sys.stdout.flush()
        cache[short] = {"skipped": True, "sha": short, "idx": idx, "message": msg}
        return cache[short]

    print(f"\n{'=' * 70}")
    print(f"  [{idx:2d}/{total}] {short}: {msg}")
    print(f"  Started: {datetime.now().strftime('%H:%M:%S')}  "
          f"({iterations} iterations) …")
    sys.stdout.flush()

    # The test image lives outside the repository (image_path), so checking
    # out arbitrary commits is safe — no need to stash or back it up.
    try:
        subprocess.run(["git", "-C", repo, "checkout", "-f", sha, "--quiet"],
                       capture_output=True, check=True)
    except subprocess.CalledProcessError as e:
        stderr = (e.stderr or b"").decode(errors="replace").strip()
        print(f"  ERROR checkout: {stderr or e}")
        sys.stdout.flush()
        return None

    try:
        t0 = datetime.now()
        install_deps(repo)
        data = run_bench(repo, image_path, iterations)
        elapsed = (datetime.now() - t0).total_seconds()
    except Exception as e:
        print(f"  ERROR: {e}")
        sys.stdout.flush()
        return None

    data.update({"sha": short, "idx": idx, "message": msg, "skipped": False})
    cache[short] = data
    t = data["time_ms"]
    m = data["memory_bytes"]
    print(f"  Done in {elapsed:.0f}s: "
          f"time median={t['median']:.1f} ms (min={t['min']:.1f} p95={t['p95']:.1f} σ={t['stdev']:.1f})  "
          f"mem median={m['median']/1024/1024:.2f} MB (σ={m['stdev']/1024/1024:.2f})")
    sys.stdout.flush()
    return data


# ---------------------------------------------------------------------------
# Statistical comparison
# ---------------------------------------------------------------------------
def is_measured(r: dict | None) -> bool:
    return r is not None and not r.get("skipped") and "time_ms" in r


def welch_t(a_samples: list[float], b_samples: list[float]) -> float:
    """Welch's t-statistic. Returns 0.0 when undefined (insufficient data)."""
    if len(a_samples) < 2 or len(b_samples) < 2:
        return 0.0
    ma, mb = statistics.fmean(a_samples), statistics.fmean(b_samples)
    va = statistics.variance(a_samples)
    vb = statistics.variance(b_samples)
    denom = math.sqrt(va / len(a_samples) + vb / len(b_samples))
    if denom == 0.0:
        return 0.0
    return (mb - ma) / denom


def is_significant(a: dict, b: dict, threshold: float, metric: str = "time_ms") -> bool:
    """A change is significant iff BOTH conditions hold:
      • the relative median delta exceeds the user threshold (effect size);
      • Welch's t-statistic on the raw samples exceeds ~2.0 (statistical
        confidence ~95 % for n ≥ 30 samples per side).
    This eliminates one-off outliers and tiny noise-level deltas."""
    if not (is_measured(a) and is_measured(b)):
        return False
    a_med = a[metric]["median"] or 1.0
    b_med = b[metric]["median"]
    rel = abs(b_med - a_med) / a_med
    if rel < threshold:
        return False
    t = abs(welch_t(a[metric]["samples"], b[metric]["samples"]))
    return t >= 2.0


def median_pct(a: dict, b: dict, metric: str = "time_ms") -> float:
    a_med = a[metric]["median"] or 1.0
    return (b[metric]["median"] - a_med) / a_med * 100


# ---------------------------------------------------------------------------
# Dichotomic bisect
# ---------------------------------------------------------------------------
def bisect(repo: str, commits: list[str], left: int, right: int,
           threshold: float, iterations: int, image_path: str,
           cache: dict, checked: set, failures: list) -> None:
    """Recursively bisect [left, right]. Commits without PHP/composer changes
    are skipped; the search keeps progressing by picking the next measured
    neighbor when needed."""
    if right - left <= 1 or (left, right) in checked:
        return
    checked.add((left, right))
    mid = (left + right) // 2
    mid_res = bench_commit(repo, commits[mid], mid, len(commits) - 1,
                           iterations, image_path, cache)
    if mid_res is None:
        failures.append(commits[mid][:8])
        return
    # When mid was skipped, treat the segment as if no measurement happened
    # there and continue bisecting both halves only if the OUTER bounds are
    # still significant — otherwise we've localised the change to one side.
    left_res = cache[commits[left][:8]]
    right_res = cache[commits[right][:8]]
    if is_measured(mid_res):
        l2m = median_pct(left_res, mid_res)
        m2r = median_pct(mid_res, right_res)
        print(f"  Segment [{left}..{mid}]: {l2m:+.1f}%  |  [{mid}..{right}]: {m2r:+.1f}%")
        sys.stdout.flush()
        if is_significant(left_res, mid_res, threshold):
            bisect(repo, commits, left, mid, threshold, iterations, image_path,
                   cache, checked, failures)
        if is_significant(mid_res, right_res, threshold):
            bisect(repo, commits, mid, right, threshold, iterations, image_path,
                   cache, checked, failures)
    else:
        # mid is skipped: recurse into both halves of the original interval
        # so we don't miss a change hiding in either side.
        bisect(repo, commits, left, mid, threshold, iterations, image_path,
               cache, checked, failures)
        bisect(repo, commits, mid, right, threshold, iterations, image_path,
               cache, checked, failures)

# ---------------------------------------------------------------------------
# Report
# ---------------------------------------------------------------------------
def print_report(repo: str, commits: list[str], cache: dict,
                 threshold: float, iterations: int, image_path: str,
                 failed: list, skipped: list) -> None:
    measured = []
    skipped_entries = []
    for idx, sha in enumerate(commits):
        short = sha[:8]
        if short in cache:
            r = dict(cache[short])
            r["idx"] = idx
            if r.get("skipped"):
                skipped_entries.append(r)
            else:
                measured.append(r)
    measured.sort(key=lambda r: r["idx"])
    now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    w = 120
    print("\n" + "=" * w)
    print("  COLOR-THIEF PHP — getPalette() PERFORMANCE BENCHMARK REPORT")
    print("=" * w)
    print(f"  Repository       : {repo}")
    print(f"  Base commit      : {commits[0][:8]}")
    print(f"  Head commit      : {commits[-1][:8]}")
    print(f"  Branch commits   : {len(commits) - 1}")
    print(f"  Commits measured : {len(measured)}")
    print(f"  Commits skipped  : {len(skipped_entries)}  (no PHP / composer changes)")
    print(f"  Commits failed   : {len(failed)}")
    print(f"  Threshold        : ±{threshold * 100:.1f}% relative median  AND  Welch |t| ≥ 2.0")
    print(f"  Iterations/run   : {iterations} × ColorThief::getPalette($path, 10, 10, null)")
    print(f"  Test image       : {image_path}")
    print(f"  Generated        : {now}")
    print()

    if not measured:
        print("No measured results — cannot produce a comparison report.")
        return

    # ---- Detail table ----
    print("DETAILED RESULTS  (per-iteration statistics; * marks significant Δ vs prev measured)")
    print("-" * w)
    hdr = (f"{'Idx':<4} {'Commit':<10} "
           f"{'Median':>9} {'Min':>8} {'P95':>8} {'σ':>7} "
           f"{'Δbase':>7} {'Δprev':>7}  "
           f"{'MemMed':>8} {'MemP95':>8} {'Memσ':>7}  Message")
    print(hdr)
    print("-" * w)
    base_med = measured[0]["time_ms"]["median"] or 1.0
    prev = None
    for r in measured:
        t = r["time_ms"]
        m = r["memory_bytes"]
        vs_base = (t["median"] - base_med) / base_med * 100
        vs_prev = median_pct(prev, r) if prev else 0.0
        flag = ""
        if prev and is_significant(prev, r, threshold):
            flag = " ⚠ DEGRADATION" if t["median"] > prev["time_ms"]["median"] else " ✅ IMPROVEMENT"
        msg = (r.get("message") or "?")[:38]
        gap = ""
        if prev and r["idx"] - prev["idx"] > 1:
            gap = f"  (+{r['idx'] - prev['idx'] - 1} commits not measured between)"
        print(
            f"[{r['idx']:>2}] {r['sha']:<10} "
            f"{t['median']:>8.1f}ms "
            f"{t['min']:>7.1f} "
            f"{t['p95']:>7.1f} "
            f"{t['stdev']:>6.1f} "
            f"{vs_base:>+6.1f}% "
            f"{vs_prev:>+6.1f}% "
            f" {m['median']/1024/1024:>6.2f}MB "
            f"{m['p95']/1024/1024:>6.2f}MB "
            f"{m['stdev']/1024/1024:>5.2f}MB  "
            f"{msg}{flag}{gap}"
        )
        prev = r

    # ---- Summary ----
    print()
    print("=" * w)
    print("SUMMARY")
    print("-" * w)
    base_r = measured[0]
    head_r = measured[-1]
    base_t = base_r["time_ms"]["median"]
    head_t = head_r["time_ms"]["median"]
    base_m = base_r["memory_bytes"]["median"] / 1024 / 1024
    head_m = head_r["memory_bytes"]["median"] / 1024 / 1024
    overall_t = (head_t - base_t) / base_t * 100 if base_t else 0.0
    overall_m = (head_m - base_m) / base_m * 100 if base_m else 0.0

    if is_significant(base_r, head_r, threshold):
        verdict_t = "DEGRADATION ⚠" if overall_t > 0 else "IMPROVEMENT ✅"
    else:
        verdict_t = "no statistically significant change"
    if is_significant(base_r, head_r, threshold, metric="memory_bytes"):
        verdict_m = "DEGRADATION ⚠" if overall_m > 0 else "IMPROVEMENT ✅"
    else:
        verdict_m = "no statistically significant change"

    print(f"  Base  [{base_r['idx']:>2}] {base_r['sha']}: "
          f"{base_t:.1f} ms (±{base_r['time_ms']['stdev']:.1f}, p95 {base_r['time_ms']['p95']:.1f})  "
          f"{base_m:.2f} MB")
    print(f"  Head  [{head_r['idx']:>2}] {head_r['sha']}: "
          f"{head_t:.1f} ms (±{head_r['time_ms']['stdev']:.1f}, p95 {head_r['time_ms']['p95']:.1f})  "
          f"{head_m:.2f} MB")
    print(f"  Overall time change   : {overall_t:+.1f}%  → {verdict_t}")
    print(f"  Overall memory change : {overall_m:+.1f}%  → {verdict_m}")

    print()
    print("  Significant transitions between consecutive measured commits:")
    prev = None
    found = False
    for r in measured:
        if prev and is_significant(prev, r, threshold):
            direction = "DEGRADATION ⚠" if r["time_ms"]["median"] > prev["time_ms"]["median"] else "IMPROVEMENT ✅"
            change_pct = median_pct(prev, r)
            t_stat = welch_t(prev["time_ms"]["samples"], r["time_ms"]["samples"])
            span = commits[prev["idx"] + 1:r["idx"] + 1]
            print(f"\n    {direction}  {change_pct:+.1f}% (Welch t={t_stat:+.2f})"
                  f"  between measured commits [{prev['idx']}..{r['idx']}]:")
            for c in span:
                short, m_ = commit_info(repo, c)
                if short in cache and cache[short].get("skipped"):
                    marker = "  ← skipped (no PHP changes)"
                elif short in cache and is_measured(cache[short]):
                    marker = "  ← measured"
                else:
                    marker = ""
                print(f"      [{commits.index(c):>2}] {short}  {m_}{marker}")
            found = True
        prev = r
    if not found:
        print("    None — all consecutive measured commits are within the configured significance bounds.")

    if failed:
        print()
        print("  Commits whose benchmark FAILED (not included in results):")
        for sha in failed:
            print(f"    • {sha}")
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
    parser.add_argument("--image",      default=os.environ.get("BENCH_IMAGE", "/tmp/bench_image.jpg"),
                        help="Path to the test image (kept OUTSIDE --repo so that "
                             "git checkout never touches it). "
                             "Default: $BENCH_IMAGE or /tmp/bench_image.jpg")
    parser.add_argument("--iterations", type=int, default=50,
                        help="getPalette calls per commit (default: 50)")
    parser.add_argument("--threshold",  type=float, default=0.05,
                        help="Relative median delta required to flag a change "
                             "(default: 0.05 = 5%%). The Welch t-test must also "
                             "confirm the change at ~95%% confidence.")
    parser.add_argument("--max-failure-ratio", type=float, default=0.30,
                        help="Abort if more than this ratio of attempted commits "
                             "fail to benchmark (default: 0.30 = 30%%).")
    parser.add_argument("--cache",      default="/tmp/perf_results.json",
                        help="JSON file to cache results across runs")
    args = parser.parse_args()
    repo       = args.repo
    threshold  = args.threshold
    iterations = args.iterations
    image_path = args.image

    if not os.path.isfile(image_path):
        sys.exit(f"ERROR: test image not found at {image_path}. "
                 f"Generate it before running (see docker-entrypoint.sh).")

    # Build ordered commit list: index 0 = base, index N = head.
    # IMPORTANT: use --format=%H to get FULL SHAs. Abbreviated SHAs would not
    # match the 8-char cache keys derived from full SHAs.
    raw = git(repo, "log", "--format=%H", f"{args.base}..{args.head}")
    if not raw:
        sys.exit(
            f"No commits found between {args.base} and {args.head}.\n"
            "  • Make sure --base is an ancestor of --head.\n"
            "  • Do not pass the bare symbolic ref 'HEAD' for --base: after a fresh\n"
            "    clone it resolves to the default branch (same as origin/main).\n"
            "    Use an explicit remote ref instead, e.g.:\n"
            "      --base origin/main --head origin/3.x-dev"
        )
    branch_commits = raw.strip().splitlines()
    branch_commits.reverse()
    base_sha = git(repo, "rev-parse", "--verify", args.base)
    commits = [base_sha] + branch_commits

    print(f"Commits on branch : {len(commits) - 1}  "
          f"(base: {commits[0][:8]} → head: {commits[-1][:8]})")
    print(f"Significance      : |Δmedian| ≥ {threshold * 100:.1f}% AND Welch |t| ≥ 2.0")
    print(f"Test image        : {image_path}")
    sys.stdout.flush()

    cache: dict = {}
    if os.path.exists(args.cache):
        try:
            with open(args.cache) as f:
                cache = json.load(f)
            # Drop entries from older schema versions to avoid mixing
            # incomparable metrics.
            cache = {k: v for k, v in cache.items()
                     if isinstance(v, dict) and (v.get("skipped") or v.get("_schema") == 2)}
        except (json.JSONDecodeError, OSError):
            cache = {}

    def save():
        with open(args.cache, "w") as f:
            json.dump(cache, f, indent=2)

    failed: list[str] = []
    attempted = 0

    def attempt(idx: int, allow_skip: bool = True) -> dict | None:
        nonlocal attempted
        sha = commits[idx]
        # Don't count cached / skipped commits as "attempts" for the
        # failure-ratio safeguard.
        short = sha[:8]
        already_cached = short in cache
        r = bench_commit(repo, sha, idx, len(commits) - 1,
                         iterations, image_path, cache, allow_skip=allow_skip)
        if not already_cached:
            attempted += 1
            if r is None:
                failed.append(short)
            save()
        if attempted >= 3 and len(failed) / attempted > args.max_failure_ratio:
            sys.exit(
                f"\nERROR: too many benchmark failures "
                f"({len(failed)}/{attempted} = "
                f"{len(failed) / attempted * 100:.0f}% > "
                f"{args.max_failure_ratio * 100:.0f}%). Aborting."
            )
        return r

    # --- Step 1: bench base and head (never skip these) ---
    base_res = attempt(0, allow_skip=False)
    head_res = attempt(len(commits) - 1, allow_skip=False)
    if not is_measured(base_res) or not is_measured(head_res):
        sys.exit("ERROR: could not bench base or head commit.")

    overall_t = median_pct(base_res, head_res)
    overall_m = median_pct(base_res, head_res, metric="memory_bytes")
    significant_t = is_significant(base_res, head_res, threshold)
    significant_m = is_significant(base_res, head_res, threshold, metric="memory_bytes")
    print(f"\nBase→Head: time {overall_t:+.1f}% "
          f"({'significant' if significant_t else 'within noise'}),  "
          f"memory {overall_m:+.1f}% "
          f"({'significant' if significant_m else 'within noise'})")
    sys.stdout.flush()

    # --- Step 2: dichotomic bisect if significant on either metric ---
    checked: set = set()
    if significant_t or significant_m:
        print("\n>> Significant change detected — bisecting commit range to locate it …")
        bisect(repo, commits, 0, len(commits) - 1, threshold,
               iterations, image_path, cache, checked, failed)
        save()
    else:
        print("\n>> Base and head are within the significance bounds — "
              "skipping bisect.")

    # --- Step 3: always bench perf-relevant commits (regardless of bisect) ---
    PERF_KEYWORDS = (
        "splfix", "double increment", "quantiz", "refactor", "rector",
        "vbox", "spl", "pixel", "typed propert",
    )
    extra_indices: set[int] = set()
    for i, sha in enumerate(commits):
        _, msg = commit_info(repo, sha)
        if any(kw in msg.lower() for kw in PERF_KEYWORDS):
            extra_indices.add(i)
    # Evenly-spaced midpoints for a smoother curve
    step = max(1, len(commits) // 6)
    extra_indices.update(range(0, len(commits), step))
    if extra_indices:
        print(f"\n>> Benching {len(extra_indices)} perf-relevant / sampling commits …")
    for idx in sorted(extra_indices):
        attempt(idx)

    # --- Step 4: restore HEAD ---
    subprocess.run(["git", "-C", repo, "checkout", "-f", args.head, "--quiet"],
                   capture_output=True)

    # --- Step 5: print report ---
    print_report(repo, commits, cache, threshold, iterations, image_path,
                 failed, skipped=[])


if __name__ == "__main__":
    main()

