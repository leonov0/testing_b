#!/usr/bin/env bash
#
# Builds the competitor handout: dist/XX_module_b_sut.zip
#
# The zip holds the SUT build including vendor/, because the competition workstation has no
# internet access. It also writes handout.sha256 - the checksum of the read-only source tree,
# used after the module to prove nothing was edited.
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT="$ROOT/dist"
STAGE="$OUT/XX_module_b"
READONLY_DIRS=(app bootstrap config database routes resources public)

rm -rf "$STAGE" "$OUT/XX_module_b_sut.zip"
mkdir -p "$STAGE"

cp -Rc "$ROOT/sut/." "$STAGE/" 2>/dev/null || cp -R "$ROOT/sut/." "$STAGE/"
rm -rf "$STAGE/dist" "$STAGE/storage/logs"/* "$STAGE/storage/framework/cache"/* \
       "$STAGE/.phpunit.cache" "$STAGE/coverage"
mkdir -p "$STAGE/storage/logs" "$STAGE/storage/framework/cache/data" \
         "$STAGE/storage/framework/sessions" "$STAGE/storage/framework/views" \
         "$STAGE/storage/app/private" "$STAGE/bootstrap/cache"

# A working database file and key so the app runs out of the box.
: > "$STAGE/database/database.sqlite"

( cd "$STAGE" && find "${READONLY_DIRS[@]}" -type f -exec shasum -a 256 {} + | sort > "$OUT/handout.sha256" )

( cd "$OUT" && zip -qr XX_module_b_sut.zip XX_module_b )
rm -rf "$STAGE"

echo "handout : $OUT/XX_module_b_sut.zip"
echo "checksum: $OUT/handout.sha256"
echo
echo "After the module, verify the read-only tree was not touched:"
echo "  cd <competitor-copy> && find ${READONLY_DIRS[*]} -type f -exec shasum -a 256 {} + | sort | diff - $OUT/handout.sha256"
