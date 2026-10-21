#!/bin/bash
# php2shell lab setup — CVE-2026-109618/CVE-2026-109619
# Pulls php:8.2-fpm and nginx:alpine from Docker Hub (requires internet on first run).
# Dockerfile.asan downloads PHP source from php.net (only needed for Chain B / UAF).
set -e

cd "$(dirname "$0")"

mkdir -p webroot/uploads
chmod 777 webroot/uploads

# PHP configuration
cat > php.ini << 'EOF'
[Session]
session.save_handler = files
session.save_path = /tmp/sessions
session.use_strict_mode = 0
session.use_cookies = 1
; CVE-2026-109618: this is the "secure" flag the bug bypasses.
; rfc1867 accepts PHPSESSID from POST body regardless of this setting.
session.use_only_cookies = 1
session.upload_progress.enabled = On
session.upload_progress.cleanup = Off
session.upload_progress.prefix = upload_progress_
session.upload_progress.name = PHP_SESSION_UPLOAD_PROGRESS
session.upload_progress.freq = 1%
session.upload_progress.min_freq = 0

[PHP]
display_errors = On
error_reporting = E_ALL
file_uploads = On
post_max_size = 128M
upload_max_filesize = 128M

[OPcache]
opcache.enable = 1
; Preload Evil gadget class at FPM startup so it exists before rfc1867 fires (Chain C)
opcache.preload = /var/www/html/preload.php
opcache.preload_user = www-data
EOF

# Nginx — routes /admin/ and /app/ to PHP-FPM, respects .user.ini per directory
cat > nginx.conf << 'EOF'
server {
    listen 80;
    root /var/www/html;
    index index.php;
    client_max_body_size 128m;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass php:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_request_buffering off;
    }

    # Deny direct access to .user.ini files
    location ~ /\.user\.ini {
        deny all;
    }
}
EOF

# Standard Dockerfile (stock php:8.2-fpm — no ASAN)
cat > Dockerfile << 'EOF'
FROM php:8.2-fpm
RUN mkdir -p /tmp/sessions && chmod 1777 /tmp/sessions
COPY php.ini /usr/local/etc/php/php.ini
EOF

# ASAN Dockerfile for Chain B (var_destroy UAF) verification.
# Compiles PHP 8.2.34 from source with Clang + AddressSanitizer.
# Build: docker build -f Dockerfile.asan -t php-asan .
# Run:   docker run --rm -v "$PWD/../poc":/poc php-asan php /poc/verify_uaf_asan.php
cat > Dockerfile.asan << 'EOF'
FROM debian:bookworm-slim

RUN apt-get update && apt-get install -y \
    clang libclang-dev \
    autoconf bison flex re2c pkg-config curl \
    libxml2-dev libsqlite3-dev libonig-dev \
    && rm -rf /var/lib/apt/lists/*

ARG PHP_VERSION=8.2.34
RUN curl -fsSL "https://www.php.net/distributions/php-${PHP_VERSION}.tar.gz" \
    | tar xz -C /usr/src && \
    cd /usr/src/php-${PHP_VERSION} && \
    CC=clang CXX=clang++ \
    CFLAGS="-fsanitize=address -g -O1" \
    LDFLAGS="-fsanitize=address" \
    ./configure \
        --with-config-file-path=/usr/local/etc/php \
        --enable-cli \
        --without-pcre-jit \
        --disable-opcache && \
    make -j$(nproc) && make install

ENV USE_ZEND_ALLOC=0
ENV ASAN_OPTIONS=detect_leaks=0:symbolize=1:halt_on_error=0
WORKDIR /app
EOF

cat > docker-compose.yml << 'EOF'
services:
  php:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: php2shell-lab
    volumes:
      - ./webroot:/var/www/html
      - ./php.ini:/usr/local/etc/php/php.ini
    security_opt: [seccomp:unconfined]
    privileged: true
    command: >
      bash -c "mkdir -p /tmp/sessions && chmod 1777 /tmp/sessions && php-fpm"

  nginx:
    image: nginx:alpine
    container_name: php2shell-nginx
    ports: ["18080:80"]
    volumes:
      - ./webroot:/var/www/html
      - ./nginx.conf:/etc/nginx/conf.d/default.conf
    depends_on: [php]
EOF

echo "Starting lab..."
docker compose up -d --build

echo ""
echo "Lab ready at http://127.0.0.1:18080"
echo ""
echo "Attack surfaces:"
echo "  Chain A — LFI:              GET  /index.php?page=/tmp/sessions/sess_<SID>"
echo "  Chain C write (php_serialize): POST /app/roster.php  (multipart)"
echo "  Chain C read  (php handler):   POST /admin/index.php (multipart)"
echo ""
echo "PoCs:"
echo "  python3 ../poc/verify_session_injection.py  --path /upload.php --lfi-path /index.php"
echo "  python3 ../poc/verify_handler_mismatch.py   --write-path /app/roster.php --read-path /admin/index.php"
echo "  ruby    ../poc/verify_session_injection.rb  --path /upload.php --lfi-path /index.php"
echo "  ruby    ../poc/verify_handler_mismatch.rb   --write-path /app/roster.php --read-path /admin/index.php"
