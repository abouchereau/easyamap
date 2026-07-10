#!/usr/bin/env bash

set -euo pipefail

usage() {
    cat <<'EOF'
Usage: scripts/package.sh [package_name] [dist_dir]

  package_name  Name of the generated folder and zip file. Defaults to "easyamap".
  dist_dir      Output directory for the package. Defaults to "<project>/dist".
EOF
}

if [[ "${1:-}" == "-h" || "${1:-}" == "--help" ]]; then
    usage
    exit 0
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

PACKAGE_NAME="${1:-easyamap}"
DIST_DIR="${2:-${PROJECT_ROOT}/dist}"
PACKAGE_DIR="${DIST_DIR}/${PACKAGE_NAME}"
APP_DIR="${PACKAGE_DIR}/app"
ARCHIVE_PATH="${DIST_DIR}/${PACKAGE_NAME}.zip"

if ! command -v rsync >/dev/null 2>&1; then
    echo "rsync is required to build the package." >&2
    exit 1
fi

if ! command -v zip >/dev/null 2>&1; then
    echo "zip is required to create the archive." >&2
    exit 1
fi

case "${PACKAGE_DIR}" in
    ""|"/"|".")
        echo "Refusing to build into an unsafe package directory: ${PACKAGE_DIR}" >&2
        exit 1
        ;;
esac

rm -rf "${PACKAGE_DIR}"
mkdir -p "${APP_DIR}"

rsync -a \
    --delete \
    --exclude '.git/' \
    --exclude '.idea/' \
    --exclude '.agents/' \
    --exclude 'nbproject/' \
    --exclude 'scripts/' \
    --exclude 'dist/' \
    "${PROJECT_ROOT}/" \
    "${APP_DIR}/"

rm -rf "${APP_DIR}/var/cache" "${APP_DIR}/var/log"
mkdir -p "${APP_DIR}/var/cache" "${APP_DIR}/var/log"

cat > "${PACKAGE_DIR}/index.php" <<'PHP'
<?php

declare(strict_types=1);

require __DIR__.'/app/public/index.php';
PHP

cat > "${PACKAGE_DIR}/.htaccess" <<'HTACCESS'
DirectoryIndex index.php
Options -Indexes

php_value max_input_vars 10000
php_value session.gc_maxlifetime 604800

<IfModule mod_headers.c>
    Header set X-XSS-Protection "1; mode=block"
    Header set X-Content-Type-Options nosniff
    Header set X-Frame-Options "SAMEORIGIN"
    Header set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    Header set Content-Security-Policy "default-src https: data: 'unsafe-inline' 'unsafe-eval'"
    Header set Referrer-Policy "same-origin"
</IfModule>

<IfModule mod_rewrite.c>
    RewriteEngine On

    # Block direct browser access to the internal application tree.
    RewriteCond %{THE_REQUEST} \s/+app/ [NC]
    RewriteRule ^app/ - [F,L]

    # Let Apache serve files that exist at the package root.
    RewriteCond %{REQUEST_FILENAME} -f [OR]
    RewriteCond %{REQUEST_FILENAME} -d
    RewriteRule ^ - [L]

    # Expose assets and public files from app/public without changing URLs.
    RewriteCond %{DOCUMENT_ROOT}/app/public/$1 -f [OR]
    RewriteCond %{DOCUMENT_ROOT}/app/public/$1 -d
    RewriteRule ^(.+)$ app/public/$1 [L]

    # Everything else goes through the Symfony front controller.
    RewriteRule ^ app/public/index.php [L]
</IfModule>
HTACCESS

(
    cd "${PACKAGE_DIR}"
    zip -qr "${ARCHIVE_PATH}" .
)

echo "Package created:"
echo "  ${PACKAGE_DIR}"
echo "  ${ARCHIVE_PATH}"
