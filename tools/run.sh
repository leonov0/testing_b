#!/usr/bin/env bash
# Starts the WORKING build (ref/) of Module B and prints where to look.
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PORT="${1:-8000}"

cd "$ROOT/ref"
php artisan migrate:fresh --seed >/dev/null
php artisan storage:link >/dev/null 2>&1 || true

cat <<INFO
Module B - Made in France products management (working build)

  public bulk GTIN verification   http://127.0.0.1:$PORT/verify
  public products API             http://127.0.0.1:$PORT/products.json
  public product page             http://127.0.0.1:$PORT/01/03000123456789   (add ?lang=fr)
  admin login                     http://127.0.0.1:$PORT/login               (passphrase: admin)
  management                      http://127.0.0.1:$PORT/products

Ctrl+C to stop.
INFO

php artisan serve --port="$PORT"
