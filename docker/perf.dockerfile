# syntax=docker/dockerfile:1
# ---------------------------------------------------------------------------
# ColorThief PHP — getPalette() dichotomic performance benchmark
#
# Clones the repository at runtime, generates a synthetic 3840×2400 test
# image, then runs the dichotomic benchmark between two git refs.
#
# Build:
#   docker build -t colorthief-bench .
#
# Run (required args):
#   docker run --rm colorthief-bench \
#       --base d6189b34 \
#       --head origin/main
#
# All optional flags are forwarded to dichotomic_bench.py:
#   --iterations  N         getPalette calls per commit  (default 50)
#   --threshold   0.10      significance threshold       (default 10 %)
#   --repo-url    URL        git clone URL               (default GitHub)
# ---------------------------------------------------------------------------
FROM php:8.4-cli-bookworm
# ---------------------------------------------------------------------------
# System deps
# ---------------------------------------------------------------------------
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libmagickwand-dev \
        python3 \
        python3-pip \
        python3-venv \
    && docker-php-ext-configure gd \
            --with-jpeg \
            --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
            gd \
            zip \
            fileinfo \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && apt-get clean && rm -rf /var/lib/apt/lists/*
# ---------------------------------------------------------------------------
# Composer
# ---------------------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
# ---------------------------------------------------------------------------
# Benchmark scripts
# ---------------------------------------------------------------------------
WORKDIR /bench
COPY perf/run_perf.php        ./run_perf.php
COPY perf/generate_image.php  ./generate_image.php
COPY perf/dichotomic_bench.py ./dichotomic_bench.py
# ---------------------------------------------------------------------------
# Entrypoint
# ---------------------------------------------------------------------------
COPY perf/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
